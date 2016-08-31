using System;
using System.Collections.Generic;
using System.Configuration;
using System.Data;
using System.Data.Common;
using System.Linq;
using System.Text.RegularExpressions;
using System.Web;
using System.Web.Security;
using System.Web.UI;
using System.Web.UI.WebControls;


namespace HPShoninAuthentication
{
    public partial class AuthenticationForm : System.Web.UI.Page
    {
        private void GetUrlList(int userId, DataTable urlList)
        {
            DbProviderFactory factory = DbProviderFactories.GetFactory("MySql.Data.MySqlClient");
            using (DbConnection connection = factory.CreateConnection())
            {
                connection.ConnectionString = ConfigurationManager.ConnectionStrings["stk"].ConnectionString;
                connection.Open();
                using (DbDataAdapter adapter = factory.CreateDataAdapter())
                {
                    adapter.SelectCommand = connection.CreateCommand();
                    adapter.SelectCommand.CommandText = @"
select
	p.site_url
from
	project_user pu
		inner join projects p
		on pu.project_id = p.id
where
	pu.is_del = 0
	and pu.user_id = @id
	and p.is_del = 0
union
select
	p.site_url
from
	projects p
where
	exists (select * from users u where u.id = @id and u.roll_cd in ('0,' '3') and u.is_del = 0)
	and p.is_del = 0";
                    DbParameter idParameter = adapter.SelectCommand.CreateParameter();
                    idParameter.ParameterName = "id";
                    idParameter.DbType = System.Data.DbType.Int32;
                    idParameter.Value = userId;
                    adapter.SelectCommand.Parameters.Add(idParameter);
                    adapter.Fill(urlList);
                }
            }
        }

        protected void Page_Load(object sender, EventArgs e)
        {
            try
            {
                int loginUserId = -1;
                DataTable urlList = new DataTable("url_list");
                urlList.Columns.Add("site_url", typeof(string));
                urlList.PrimaryKey = new DataColumn[] { urlList.Columns[0] };
                if (Request.Cookies[ConfigurationManager.AppSettings["hpshonin_cookie_name"]] != null)
                {
                    int userId = -1;
                    DbProviderFactory factory = DbProviderFactories.GetFactory("MySql.Data.MySqlClient");
                    using (DbConnection connection = factory.CreateConnection())
                    {
                        connection.ConnectionString = ConfigurationManager.ConnectionStrings["stk"].ConnectionString;
                        connection.Open();
                        using (DbCommand command = connection.CreateCommand())
                        {
                            command.CommandText = "select user_id, modified from authentications where cookie_id = @id";
                            DbParameter idParameter = command.CreateParameter();
                            idParameter.ParameterName = "id";
                            idParameter.DbType = DbType.String;
                            idParameter.Value = HttpUtility.UrlDecode(Request.Cookies[ConfigurationManager.AppSettings["hpshonin_cookie_name"]].Value);
                            command.Parameters.Add(idParameter);
                            using (DbDataReader reader = command.ExecuteReader())
                            {
                                if (reader.Read() && (DateTime.Now - reader.GetDateTime(1)).TotalMinutes <= int.Parse(ConfigurationManager.AppSettings["session_timuout"]))
                                {
                                    userId = reader.GetInt32(0);
                                    loginUserId = userId;
                                }
                            }
                            command.CommandText = "update authentications set modified = @modified where cookie_id = @id";
                            DbParameter modifiedParameter = command.CreateParameter();
                            modifiedParameter.ParameterName = "modified";
                            modifiedParameter.DbType = System.Data.DbType.DateTime;
                            modifiedParameter.Value = DateTime.Now;
                            command.Parameters.Add(modifiedParameter);
                            command.ExecuteNonQuery();
                        }
                    }
                    if (userId != -1)
                    {
                        GetUrlList(userId, urlList);
                    }
                }
                if (Request.Cookies[ConfigurationManager.AppSettings["mt_cookie_name"]] != null)
                {
                    bool isValidSession = false;
                    Regex regEx = new Regex("^([^:]+)::([^:]+)::");
                    Match match = regEx.Match(HttpUtility.UrlDecode(Request.Cookies[ConfigurationManager.AppSettings["mt_cookie_name"]].Value));
                    if (match.Success)
                    {
                        int userId = -1;
                        DbProviderFactory factory = DbProviderFactories.GetFactory("MySql.Data.MySqlClient");
                        using (DbConnection connection = factory.CreateConnection())
                        {
                            connection.ConnectionString = ConfigurationManager.ConnectionStrings["stk"].ConnectionString;
                            connection.Open();
                            using (DbCommand command = connection.CreateCommand())
                            {
                                command.CommandText = "select count(*) from mt_session where session_id = @id and session_kind = 'US'";
                                DbParameter idParameter = command.CreateParameter();
                                idParameter.ParameterName = "id";
                                idParameter.DbType = DbType.String;
                                idParameter.Value = match.Groups[2].Value;
                                command.Parameters.Add(idParameter);
                                if ((int)command.ExecuteScalar() > 0)
                                {
                                    isValidSession = true;
                                }
                            }
                            if (isValidSession)
                            {
                                using (DbCommand command = connection.CreateCommand())
                                {
                                    command.CommandText = "select id from users where email = @email and is_del = 0";
                                    DbParameter emailParameter = command.CreateParameter();
                                    emailParameter.ParameterName = "email";
                                    emailParameter.DbType = DbType.String;
                                    emailParameter.Value = match.Groups[1].Value;
                                    command.Parameters.Add(emailParameter);
                                    object id = (int)command.ExecuteScalar();
                                    if (id != null)
                                    {
                                        userId = (int)id;
                                        if (loginUserId == -1)
                                        {
                                            loginUserId = userId;
                                        }
                                    }
                                }
                                if (userId != -1)
                                {
                                    GetUrlList(userId, urlList);
                                }
                            }
                        }
                    }
                }

                foreach (DataRow row in urlList.Rows)
                {
                    row[0] = ((string)row[0]).ToLower();
                }
                urlList.AcceptChanges();

                string url = FormsAuthentication.GetRedirectUrl(loginUserId.ToString(), false);
                string[] patterns = {ConfigurationManager.AppSettings["url_editing"]
                                    , ConfigurationManager.AppSettings["url_preview"]
                                    , ConfigurationManager.AppSettings["url_staging"]};
                foreach (string pattern in patterns)
                {
                    Regex regEx = new Regex(pattern);
                    Match match = regEx.Match(url);
                    if (!match.Success)
                    {
                        continue;
                    }
                    if (urlList.Select("site_url = '" + match.Groups[1].Value.ToLower() + "'").Length == 0)
                    {
                        Response.ClearContent();
                        Response.StatusCode = 401;
                        return;
                    }
                }
                FormsAuthentication.RedirectFromLoginPage(loginUserId.ToString(), false);
            }
            catch
            {
                Response.ClearContent();
                Response.StatusCode = 401;
            }
        }
    }
}