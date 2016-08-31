using System;
using System.IO;
using System.Text.RegularExpressions;
using HtmlAgilityPack;

namespace ConvertHTML
{
    class Converter
    {
        static int Main(string[] args)
        {
            try
            {
                string[] removeElements =  {
                    "script"
                    , "comment()"
                };
                HtmlDocument html = new HtmlDocument();
                System.Text.Encoding encording = html.DetectEncoding(args[0]);
                if (encording != null)
                {
                    html.Load(args[0], encording);
                }
                else
                {
                    html.Load(args[0], System.Text.Encoding.UTF8);
                }
                HtmlNodeCollection nodes = null;
                foreach (string removeElement in removeElements)
                {
                    nodes = html.DocumentNode.SelectNodes(string.Format("//{0}", removeElement));
                    if (nodes != null)
                    {
                        foreach (HtmlNode node in nodes)
                        {
                            node.Remove();
                        }
                    }
                }
                nodes = html.DocumentNode.SelectNodes("html/body");
                string text = nodes[0].InnerText;
                string[] removePatterns = {
                    @"^\s*\r\n"
                    , @"^\s*\r"
                    , @"^\s*\n"
                };
                foreach (string removePattern in removePatterns)
                {
                    Regex regEx = new Regex(removePattern, RegexOptions.Multiline);
                    text = regEx.Replace(text, string.Empty);
                }
                string filePath = Path.GetTempFileName();
                File.WriteAllText(filePath, text, System.Text.Encoding.UTF8);
                Console.Write(filePath);
                return 0;
            }
            catch(Exception ex)
            {
                return -1;
            }
        }
    }
}
