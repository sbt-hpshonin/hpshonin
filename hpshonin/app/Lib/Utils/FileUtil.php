<?php

App::import('Vendor', 'Archive_Zip', array('file' => 'Archive' . DS . 'Zip.php'));
App::uses('StringUtil', 'Lib/Utils');

/**
 * ファイル関連ユーティリティクラスです。
 *
 * @author tasano
 *
 */
class FileUtil {

	/** ハッシュのアルゴリズム*/
	const ALGO = 'md5';

	/**
	 * ファイルをコピーします。
	 *
	 * @param  unknown $source コピー元
	 * @param  unknown $dest   コピー先
	 * @return boolean         成否
	 */
	public static function copy($source, $dest) {

		CakeLog::debug('FileUtil::copy');
		CakeLog::debug('  $source：[' . $source . ']');
		CakeLog::debug('  $dest  ：[' . $dest . ']');

		// 引数チェック
		if (empty($source) || empty($dest)) {
			return false;
		}

		return copy($source, $dest);
	}

	/**
	 * フォルダを生成します。
	 *
	 * @param  unknown $dir フォルダパス名
	 * @return boolean      成否
	 */
	public static function mkdir($dir) {

		CakeLog::debug('FileUtil::mkdir');
		CakeLog::debug('  $dir：[' . $dir . ']');

		// 既に存在する場合はスキップ
		if (FileUtil::exists($dir)) {
			CakeLog::debug('dir already exists. $dir：[' . $dir . ']');
			return true;
		}
		return mkdir($dir, 0, true);
	}

	/**
	 * ファイルの存在確認を行います。
	 *
	 * @param  unknown $file ファイルパス名
	 * @return boolean       true：存在する、false：存在しない。
	 */
	public static function exists($file) {

		CakeLog::debug('FileUtil::exists');
		CakeLog::debug('  $file：[' . $file . ']');

		// 引数チェック
		if (empty($file)) {
			return false;
		}

		return file_exists($file);
	}

	/**
	 * ファイルをリネームします。
	 *
	 * @param  unknown $file ファイルパス名
	 * @return boolean       true：成功、false：失敗
	 */
	public static function rename($oldname, $newname) {

		CakeLog::debug('FileUtil::rename');
		CakeLog::debug('  $oldname：[' . $oldname . ']');
		CakeLog::debug('  $newname：[' . $newname . ']');

		// 引数チェック
		if (empty($oldname) || empty($newname)) {
			return false;
		}

		return rename($oldname, $newname);
	}

	/**
	 * ファイルサイズを返却します。
	 *
	 * @param  unknown $file  ファイルパス名
	 * @return boolean|number ファイルサイズ
	 */
	public static function size($file) {

		CakeLog::debug('FileUtil::size');
		CakeLog::debug('  $file：[' . $file . ']');

		// 引数チェック
		if (empty($file)) {
			return false;
		}

		return filesize($file);
	}

	/**
	 * ファイルの内容を返却します。
	 *
	 * @param  unknown $file  ファイルパス名
	 * @return boolean|string ファイルの内容
	 */
	public static function fileGetContents($file) {

		CakeLog::debug('FileUtil::fileGetContents');
		CakeLog::debug('  $file：[' . $file . ']');

		// 引数チェック
		if (empty($file)) {
			return false;
		}

		return	file_get_contents($file);
	}

	/**
	 * ファイル、フォルダを削除します。
	 *
	 * @param  unknown $file ファイルパス名
	 * @return boolean       成否
	 */
	public static function remove($file) {

		CakeLog::debug('FileUtil::remove');
		CakeLog::debug('  $file：[' . $file . ']');

		// 引数チェック
		if (empty($file)) {
			return false;
		}

		if (file_exists($file)) {
			if (is_dir($file)) {
				return rmdir($file);
			} else {
				return unlink($file);
			}
		} else {
			CakeLog::debug('file is not exist!');
			return true;
		}
	}

	/**
	 * Zipファイルを解凍します。(日付が解凍日付になるため使っていない)
	 *
	 * @param  unknown $zipFile Zipファイルパス名
	 * @param  unknown $to      解凍先パス名
	 * @return boolean          成否
	 */
	public static function extract_old($zipFile, $to) {

		CakeLog::debug('FileUtil::extract');
		CakeLog::debug('  $zipFile：[' . $zipFile . ']');
		CakeLog::debug('  $to     ：['. $to . ']');

		// 引数チェック
		if (empty($zipFile) || empty($to)) {
			return false;
		}

		// インスタンス生成
		$zip = new ZipArchive();
		if ($zip === false) {
			return false;
		}

		// ファイルオープン
		if ($zip->open($zipFile) === false) {
			return false;;
		}

		// 解凍、展開
		if ($zip->extractTo($to) === false) {
			return false;
		}

		// ファイルクローズ
		if ($zip->close() === false) {
			return false;
		}

		return true;
	}

	/**
	 * Zipファイルを解凍します。
	 *
	 * @param  unknown $zipFile Zipファイルパス名
	 * @param  unknown $to      解凍先パス名
	 * @return boolean          成否
	 */
	public static function extract($zipFile, $to) {
		$zipFile = str_replace('\\', '/', $zipFile);
		$to = str_replace('\\', '/', $to);

		CakeLog::debug('FileUtil::extract');
		CakeLog::debug('  $zipFile：[' . $zipFile . ']');
		CakeLog::debug('  $to     ：['. $to . ']');

		// 引数チェック
		if (empty($zipFile) || empty($to)) {
			return false;
		}

		// インスタンス生成
		$zip = new Archive_Zip($zipFile);

		// 解凍先
		$option = array('add_path' => $to);

		// 解凍、展開
		if ($zip->extract($option) === 0) {
			return false;
		}

		return true;
	}

	/**
	 * ファイルの内容のハッシュ値を返却します。
	 *
	 * @param  unknown $file  ファイルパス名
	 * @return boolean|string ハッシュ値
	 */
	public static function hash($file, $exclude) {

		CakeLog::debug('FileUtil::hash');
		CakeLog::debug('  $file   ：[' . $file . ']');
		CakeLog::debug('  $exclude：[' . $exclude . ']');

		// 引数チェック
		if (empty($file)) {
			return false;
		}

		// ファイルの内容を取得
		$contents = FileUtil::fileGetContents($file);
		if($contents === false) {
			return false;
		}

		if ('html' == FileUtil::getExtention($file)) {
			if (empty($exclude) === false) {
				$contents = StringUtil::remove($contents, $exclude);
			}
		}

		// ハッシュ値に変換後、返却
		CakeLog::debug(hash(self::ALGO, $contents));
		return hash(self::ALGO, $contents);
	}

	/**
	 * ファイル更新日時を返却します。
	 *
	 * @param  unknown $file  ファイルパス名
	 * @return boolean|string ファイル更新日時
	 */
	public static function filetime($file) {

		CakeLog::debug('FileUtil::filetime');
		CakeLog::debug('  $file：[' . $file . ']');

		// 引数チェック
		if (empty($file)) {
			return false;
		}

		// ファイル更新日時を取得
		$filetime = filemtime($file);
		if ($filetime === false) {
			return false;
		}

		// フォーマット後、返却
		return date("y.m.d H:i:s", $filetime);
	}

	/**
	 * ファイルの内容を置換します。
	 *
	 * @param  unknown $file   ファイルパス名
	 * @param  unknown $before 対象文字列
	 * @param  unknown $after  置換文字列
	 * @return boolean|number  成否
	 */
	public static function replaceContents($file, $before, $after) {

		CakeLog::debug('FileUtil::replaceContents');
		CakeLog::debug('  $file  ：[' . $file . ']');
		CakeLog::debug('  $before：[' . $before . ']');
		CakeLog::debug('  $after ：[' . $after . ']');

		// 引数チェック
		if (empty($file) || empty($before) || empty($after)) {
			return false;
		}

		$keys[$before] = $after;
		$buff = file_get_contents($file);
		if($buff === false) {
			return false;
		}
		$buff = strtr($buff, $keys);
		if($buff === false) {
			return false;
		}
		return file_put_contents($file, $buff);
	}

	/**
	 * 再帰的にファイルの内容を置換します。
	 *
	 * @param  unknown $file   ファイルパス名
	 * @param  unknown $before 置換前文字列
	 * @param  unknown $after  置換後文字列
	 * @return boolean         成否
	 */
	public static function replaceContentsAll($dir, $before, $after, $extension) {

		CakeLog::debug('FileUtil::replaceContentsAll');
		CakeLog::debug('  $dir      ：[' . $dir . ']');
		CakeLog::debug('  $before   ：[' . $before . ']');
		CakeLog::debug('  $after    ：[' . $after . ']');
		CakeLog::debug('  $extension：[' . $extension . ']');

		// 引数チェック
		if (empty($dir) || empty($before) || empty($after) || empty($extension)) {
			return false;
		}

		$list = self::recursivecSanDirExtention($dir, $extension);
		if ($list === false) {
			return false;
		}
		foreach($list as $file) {
			if (is_file($file)) {
				self::replaceContents($file, $before, $after);
			}
		}
		return true;
	}

	/**
	 * 再帰的にコピーします。
	 *
	 * @param  unknown $dir_name コピー元
	 * @param  unknown $new_dir  コピー先
	 * @return boolean           成否
	 */
	public static function dirCopy($dir_name, $new_dir) {

		CakeLog::debug('FileUtil::dirCopy');
		CakeLog::debug('  $dir_name：[' . $dir_name . ']');
		CakeLog::debug('  $new_dir ：[' . $new_dir . ']');

		// 引数チェック
		if (empty($dir_name) || empty($new_dir)) {
			return false;
		}

		if (FileUtil::exists($dir_name) === false) {
			CakeLog::debug('dir is not exist! $dir_name：['. $dir_name . ']');
			return true;
		}

		if (!is_dir($new_dir)) {
			if(mkdir($new_dir, 0, true) === false) {
				return false;
			}
		}
		if (is_dir($dir_name)) {
			if ($dh = opendir($dir_name)) {
				while (($file = readdir($dh)) !== false) {
					if ($file == "." || $file == "..") {
						continue;
					}
					if (is_dir($dir_name . DS . $file)) {
						self::dirCopy($dir_name . DS . $file, $new_dir . DS . $file);
					}
					else {
						if(copy($dir_name . DS . $file, $new_dir . DS . $file) === false) {
							return false;
						}
					}
				}
				if(closedir($dh) === false) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 再帰的にコピーします。
	 *
	 * @param  unknown $dir_name コピー元
	 * @param  unknown $new_dir  コピー先
	 * @return boolean           成否
	 */
	public static function dirCopyExclude($dir_name, $new_dir, $exclude) {

		CakeLog::debug('FileUtil::dirCopyExclude');
		CakeLog::debug('  $dir_name：[' . $dir_name . ']');
		CakeLog::debug('  $new_dir ：[' . $new_dir . ']');
		CakeLog::debug('  $exclude ：[' . $exclude . ']');

		// 引数チェック
		if (empty($dir_name) || empty($new_dir)) {
			return false;
		}

		$exclude2 = $dir_name . DS . $exclude;

		if ($dir_name === $exclude2) {
			return true;
		}

		if (FileUtil::exists($dir_name) === false) {
			CakeLog::debug('dir is not exist! $dir_name：['. $dir_name . ']');
			return true;
		}

		if (!is_dir($new_dir)) {
			if(mkdir($new_dir, 0, true) === false) {
				return false;
			}
		}
		if (is_dir($dir_name)) {
			if ($dh = opendir($dir_name)) {
				while (($file = readdir($dh)) !== false) {
					if ($file == "." || $file == "..") {
						continue;
					}
					if (is_dir($dir_name . DS . $file)) {
						self::dirCopyExcludeInner($dir_name . DS . $file, $new_dir . DS . $file, $exclude2);
					}
					else {
						if(copy($dir_name . DS . $file, $new_dir . DS . $file) === false) {
							return false;
						}
					}
				}
				if(closedir($dh) === false) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 再帰的にコピーします。
	 *
	 * @param  unknown $dir_name コピー元
	 * @param  unknown $new_dir  コピー先
	 * @return boolean           成否
	 */
	private static function dirCopyExcludeInner($dir_name, $new_dir, $exclude) {

		CakeLog::debug('FileUtil::dirCopyExcludeInner');
		CakeLog::debug('  $dir_name：[' . $dir_name . ']');
		CakeLog::debug('  $new_dir ：[' . $new_dir . ']');
		CakeLog::debug('  $exclude ：[' . $exclude . ']');

		// 引数チェック
		if (empty($dir_name) || empty($new_dir)) {
			return false;
		}

		if ($dir_name === $exclude) {
			return true;
		}

		if (FileUtil::exists($dir_name) === false) {
			CakeLog::debug('dir is not exist! $dir_name：['. $dir_name . ']');
			return true;
		}

		if (!is_dir($new_dir)) {
			if(mkdir($new_dir, 0, true) === false) {
				return false;
			}
		}
		if (is_dir($dir_name)) {
			if ($dh = opendir($dir_name)) {
				while (($file = readdir($dh)) !== false) {
					if ($file == "." || $file == "..") {
						continue;
					}
					if (is_dir($dir_name . DS . $file)) {
						self::dirCopyExclude($dir_name . DS . $file, $new_dir . DS . $file, $exclude);
					}
					else {
						if(copy($dir_name . DS . $file, $new_dir . DS . $file) === false) {
							return false;
						}
					}
				}
				if(closedir($dh) === false) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 再帰的に削除します。
	 *
	 * @param  unknown $dir 削除対象ルートフォルダ
	 * @return boolean      成否
	 */
	public static function rmdirAll($dir) {

		CakeLog::debug('FileUtil::rmdirAll');
		CakeLog::debug('  $dir：[' . $dir . ']');

		// 引数チェック
		if (empty($dir)) {
			return false;
		}

		if (!FileUtil::exists($dir)) {
			CakeLog::debug('$dir is not exists!!');
			return true;
		}

		$dhandle = opendir($dir);
		if ($dhandle === false) {
			return false;
		}

		if ($dhandle) {
			while (false !== ($fname = readdir($dhandle))) {
				if (is_dir($dir . DS .$fname)) {
					if (($fname != '.') && ($fname != '..')) {
						self::rmdirAll($dir . DS . $fname);
					}
				} else {
					if(unlink($dir . DS .$fname) === false) {
						return false;
					}
				}
			}
			if(closedir($dhandle) === false) {
				return false;
			}
		}

		if(rmdir($dir) === false) {
			return false;
		}

		return true;
	}

	/**
	 * 再帰的に削除します。
	 *
	 * @param  unknown $dir     削除対象ルートフォルダ
	 * @param  unknown $exclude 削除対象除外ルートフォルダ
	 * @return boolean          成否
	 */
	public static function rmdirExclude($dir, $exclude) {

		CakeLog::debug('FileUtil::rmdirExclude');
		CakeLog::debug('  $dir     ：[' . $dir . ']');
		CakeLog::debug('  $$exclude：[' . $exclude . ']');

		// 引数チェック
		if (empty($dir)) {
			return false;
		}

		if (!FileUtil::exists($dir)) {
			CakeLog::debug('$dir is not exists!!');
			return true;
		}

		if ($dir === $exclude) {
			return true;
		}

		$dhandle = opendir($dir);
		if ($dhandle === false) {
			return false;
		}

		if ($dhandle) {
			while (false !== ($fname = readdir($dhandle))) {
				if (is_dir($dir . DS .$fname)) {
					if (($fname != '.') && ($fname != '..')) {
						self::rmdirExcludeInner($dir . DS . $fname, $exclude);
					}
				} else {
					if(unlink($dir . DS .$fname) === false) {
						return false;
					}
				}
			}
			if(closedir($dhandle) === false) {
				return false;
			}
		}

		return true;
	}

	/**
	 * 再帰的に削除します(内部)。
	 *
	 * @param  unknown $dir 削除対象ルートフォルダ
	 * @param  unknown $exclude 削除対象除外ルートフォルダ
	 * @return boolean      成否
	 */
	private static function rmdirExcludeInner($dir, $exclude) {

		CakeLog::debug('FileUtil::rmdirExclude');
		CakeLog::debug('  $dir     ：[' . $dir . ']');
		CakeLog::debug('  $$exclude：[' . $exclude . ']');

		// 引数チェック
		if (empty($dir)) {
			return false;
		}

		if (!FileUtil::exists($dir)) {
			CakeLog::debug('$dir is not exists!!');
			return true;
		}

		if ($dir === $exclude) {
			return true;
		}

		$dhandle = opendir($dir);
		if ($dhandle === false) {
			return false;
		}

		if ($dhandle) {
			while (false !== ($fname = readdir($dhandle))) {
				if (is_dir($dir . DS .$fname)) {
					if (($fname != '.') && ($fname != '..')) {
						self::rmdirExcludeInner($dir . DS . $fname, $exclude);
					}
				} else {
					if(unlink($dir . DS .$fname) === false) {
						return false;
					}
				}
			}
			if(closedir($dhandle) === false) {
				return false;
			}
		}

		if(rmdir($dir) === false) {
			return false;
		}

		return true;
	}

	/**
	 * 再帰的にファイルリストを返却します。
	 *
	 * @param  unknown $target         検索フォルダ
	 * @return boolean|multitype:mixed ファイルリスト
	 */
	public static function getFileListFromDir($target) {

		CakeLog::debug('FileUtil::getFileListFromDir');
		CakeLog::debug('  $target：[' . $target . ']');

		// 引数チェック
		if (empty($target)) {
			return false;
		}

		$list = array();

		foreach(glob($target . '*' . DS, GLOB_ONLYDIR) as $val){
            $list += self::getFileListFromDir($val);
        }
        foreach(glob($target . '{*.*}', GLOB_BRACE) as $val2){
            $list[] = $val2;
        }

		return $list;
	}

	/**
	 * 再帰的にファイルリストを返却します。
	 * さらに指定された文字列を除去します。
	 *
	 * @param  unknown $target         検索フォルダ
	 * @param  unknown $remove_string  削除文字列
	 * @return boolean|multitype:mixed ファイルリスト
	 */
	public static function getFileListFromDirAndRemoveString($target, $remove_dir) {

		CakeLog::debug('FileUtil::getFileListFromDirAndRemoveString');
		CakeLog::debug('  $target    ：[' . $target . ']');
		CakeLog::debug('  $remove_dir：['. $remove_dir . ']');

		// 引数チェック
		if (empty($target) || empty($remove_dir)) {
			return false;
		}

		$list = self::recursivecSanDir($target);
		if($list === false) {
			return false;
		}
		$list2 = array();
		foreach ($list as $row) {
			$work = StringUtil::ltrimOnce($row, $remove_dir . DS);
			if($work === false) {
				return false;
			}
			$list2[] = $work;
		}
		return $list2;
	}

	/**
	 * ファイルの内容に差異があるか判定します。
	 *
	 * @param  unknown $file1 ファイルパス名１
	 * @param  unknown $file2 ファイルパス名２
	 * @return boolean        true：差異がある、false：差異がない
	 */
	public static function hasDiffContents($file1, $file2) {

		CakeLog::debug('FileUtil::hasDiffContents');
		CakeLog::debug('  $file1：[' . $file1 . ']');
		CakeLog::debug('  $file2：['. $file2 . ']');

		// 引数チェック
		if (empty($file1) && empty($file2)) {
			return false;
		}
		if (empty($file1) || empty($file2)) {
			return true;
		}

		if (!self::exists($file1) && !self::exists($file2)) {
			CakeLog::debug('both no exists');
			return false;
		}
		if (!self::exists($file1) || !self::exists($file2)) {
			CakeLog::debug('katahou no exists');
			return true;
		}

		// ファイルサイズ比較
		$size1 = self::size($file1);
		CakeLog::debug('$size1：[' . $size1 . ']');
		$size2 = self::size($file2);
		CakeLog::debug('$size2：[' . $size2 . ']');
		if ($size1 != $size2) {
			CakeLog::debug('size diff');
			return true;
		}

		// ハッシュ比較
		$hash1 = self::hash($file1);
		CakeLog::debug('$hash1：[' . $hash1 . ']');
		$hash2 = self::hash($file2);
		CakeLog::debug('$hash2：[' . $hash2 . ']');

		if ($hash1 != $hash2) {
			CakeLog::debug('hash diff');
			return true;
		}

		CakeLog::debug('no diff');
		return false;
	}

	/**
	 * 指定した全てのファイルリストにて、ファイルの内容に差異があるか判定します。
	 *
	 * @param  unknown $file_list ファイルリスト
	 * @param  unknown $dir1 比較対象フォルダ名１
	 * @param  unknown $dir2 比較対象フォルダ名２
	 * @return boolean       true：差異がある、false：差異がない
	 */
	public static function hasDiffContentsAll($file_list, $dir1, $dir2) {

		CakeLog::debug('FileUtil::hasDiffContentsAll');
		CakeLog::debug('  $file_list：[' . $file_list . ']');
		CakeLog::debug('  $dir1：[' . $dir1 . ']');
		CakeLog::debug('  $dir2：[' . $dir2 . ']');

		// 引数チェック
		if (!isset($file_list) || !isset($dir1) || !isset($dir2)) {
			return false;
		}

		foreach ($file_list as $fileName) {
			if (self::hasDiffContents($dir1 . DS .  $fileName, $dir2 . DS . $fileName) === false) {
				CakeLog::error('差異がありません。');
				CakeLog::error(' ファイル１:[' . $dir1 . DS .  $fileName . ']');
				CakeLog::error(' ファイル２:[' . $dir2 . DS .  $fileName . ']');
				return false; //差異がないファイルが存在する
			}
		}

		CakeLog::debug('has diff2');
		return true; // 全てのファイルに差異が存在する
	}

	/**
	 * 内容に差異がないファイルを取得します。
	 *
	 * @param  unknown $file_list ファイルリスト
	 * @param  unknown $dir1 比較対象フォルダ名１
	 * @param  unknown $dir2 比較対象フォルダ名２
	 * @return string        内容に差異がないファイルパス名
	 */
	public static function getNoDiffContents($file_list, $dir1, $dir2) {

		CakeLog::debug('FileUtil::getNoDiffContents');
		CakeLog::debug('  $file_list：[' . $file_list . ']');
		CakeLog::debug('  $dir1：[' . $dir1 . ']');
		CakeLog::debug('  $dir2：[' . $dir2 . ']');

		// 引数チェック
		if (!isset($file_list) || !isset($dir1) || !isset($dir2)) {
			return false;
		}

		foreach ($file_list as $fileName) {
			if (self::hasDiffContents($dir1 . DS .  $fileName, $dir2 . DS . $fileName) === false) {
				CakeLog::error('差異のファイル');
				CakeLog::error(' ファイル１:[' . $dir1 . DS .  $fileName . ']');
				CakeLog::error(' ファイル２:[' . $dir2 . DS .  $fileName . ']');
				return $fileName; //差異がないファイルが存在する
			}
		}

		CakeLog::debug('has diff3');
		return null; // 全てのファイルに差異が存在する
	}

	/**
	 * 指定されたファイルを読み込み、ファイルリストを取得します。
	 *
	 * @param unknown $file            ファイルパス名
	 * @return boolean|multitype:mixed ファイルリスト
	 */
	public static function getFileListFromFileContents($file) {

		CakeLog::debug('FileUtil::getFileListFromFileContents');
		CakeLog::debug('  $file：[' . $file . ']');

		// 引数チェック
		if (empty($file)) {
			return false;
		}

		$pathNames = array();

		//ファイルを開く ※モード[r]の読み込み専用
		if (($fp = fopen ($file, 'r' )) === false) {
			return false;
		}

		//ファイルの読み込みと表示
		//１行ずつファイルを読み込んで、表示する。
		while (!feof ($fp)) {
			$row = fgets ($fp, 4096);
			if (empty($row)) {
				continue;
			}

			// 改行コードを除去
			$work = str_replace(array("\r\n","\r","\n"), "",  $row);

			$pathNames[] = $work;
		}

		//ファイルを閉じる
		if (fclose($fp) === false) {
			return false;
		}

		return $pathNames;
	}

	/**
	 * 指定されたファイルを読み込み、ファイルリストを取得します。
	 *
	 * @param unknown $file            ファイルパス名
	 * @return boolean|multitype:mixed ファイルリスト
	 */
	public static function getFileListFromFileContents2($file) {

		CakeLog::debug('FileUtil::getFileListFromFileContents2');
		CakeLog::debug('  $file：[' . $file . ']');

		// 引数チェック
		if (empty($file)) {
			return false;
		}

		$pathNames = array();

		//ファイルを開く ※モード[r]の読み込み専用
		if (($fp = fopen ($file, 'r' )) === false) {
			return false;
		}

		//ファイルの読み込みと表示
		//１行ずつファイルを読み込んで、表示する。
		while (!feof ($fp)) {
			$row = fgets ($fp, 4096);
			if (empty($row)) {
				continue;
			}

			// 改行コードを除去
			$work = str_replace(array("\r\n","\r","\n"), "",  $row);

			// スラッシュをDSへ変換
			$work = str_replace( '/', DS, $work);

			$pathNames[] = $work;
		}

		//ファイルを閉じる
		if (fclose($fp) === false) {
			return false;
		}

		return $pathNames;
	}

	/**
	 * 指定されたファイルを読み込み、ファイルリストを取得します。
	 * また、指定されたフォルダを除去します。
	 *
	 * @param  unknown $file           ファイルパス名
	 * @param  unknown $remove_dir     除去フォルダ名
	 * @return boolean|multitype:mixed ファイルリスト
	 */
	public static function getFileListFromFileContentsAndRemoveDir($file, $remove_dir) {

		CakeLog::debug('FileUtil::getFileListFromFileContentsAndRemoveDir');
		CakeLog::debug('  $file      ：[' . $file . ']');
		CakeLog::debug('  $remove_dir：[' . $remove_dir . ']');

		// 引数チェック
		if (empty($file)) {
			return false;
		}

		$pathNames = array();

		//ファイルを開く ※モード[r]の読み込み専用
		if (($fp = fopen ($file, 'r' )) === false) {
			CakeLog::debug('fail to open file. filename：[' . $file . ']');
			return false;
		}

		//ファイルの読み込みと表示
		//１行ずつファイルを読み込んで、表示する。
		while (!feof ($fp)) {
			$row = fgets ($fp, 4096);
			if (empty($row)) {
				continue;
			}
			// 改行コードを除去
			$work = str_replace(array("\r\n","\r","\n"), "",  $row);
			// スラッシュをDSへ変換
			$work = str_replace( '/', DS, $work);
			// DSをトリム
			$work = trim($work, DS);
			// 指定文字列を除去
			// 2013.10.23 H.Suzuki Changed
			// $work = StringUtil::ltrimOnce($work, $remove_dir . DS);
			if(StringUtil::ltrimOnce($work, $remove_dir) != ""){
				$work = StringUtil::ltrimOnce($work, $remove_dir . DS);
			}
			else{
				$work = StringUtil::ltrimOnce($work, $remove_dir);
			}
			// 2013.10.23 H.Suzuki Changed END
			
			// 追加
			$pathNames[] = $work;
		}

		//ファイルを閉じる
		if (fclose($fp) === false) {
			CakeLog::debug('fail to close file. filename：[' . $file . ']');
			return false;
		}

		return $pathNames;
	}

	/**
	 * 再帰的にファイルを検索します。
	 *
	 * @param  unknown $path   パス
	 * @param  unknown $result 作業用領域
	 * @return string          ファイルリスト
	 */
	public static function recursivecSandir($path) {

		CakeLog::debug('FileUtil::recursivecSandir');
		CakeLog::debug('  $path：[' . $path . ']');

		// 引数チェック
		if (empty($path)) {
			return false;
		}

		$result = self::recursivecSandirInner($path);

		$result2 = array();
		foreach ($result as $file) {
			if (is_file($file)) {
				$result2[] = $file;
			}
		}

		return $result2;
	}

	/**
	 * 再帰的にファイルを検索します(内部)。
	 *
	 * @param  unknown $path   パス
	 * @param  unknown $result 作業用領域
	 * @return string          ファイルリスト
	 */
	private static function recursivecSandirInner($path, $result=array()) {

		CakeLog::debug('FileUtil::recursivecSandirInner');
		CakeLog::debug('  $path：[' . $path . ']');

		// 引数チェック
		if (empty($path)) {
			return false;
		}

		if (is_dir($path)) {
			$path = rtrim($path, DS). DS;
			$dirs = array_diff(scandir($path), array('.', '..'));
			foreach ($dirs as $dir) {
				if (is_dir($path. $dir)) {
					$result[] = $path. $dir. DS;
					$result = self::recursivecSandirInner($path. $dir. DS, $result);
				} else if (is_file($path. $dir)) {

					$result[] = $path. $dir;

				}
			}
		}
		return $result;
	}

	/**
	 * 再帰的にファイルを検索し、指定した拡張子のファイルリストを返却します。
	 *
	 * @param  unknown $dir             ディレクトリ
	 * @param  unknown $extension       拡張子
	 * @return boolean|multitype:string ファイルリスト
	 */
	public static function recursivecSanDirExtention($dir, $extension) {

		CakeLog::debug('FileUtil::recursivecSanDirExtention');
		CakeLog::debug('  $dir      ：[' . $dir . ']');
		CakeLog::debug('  $extension：[' . $extension . ']');

		// 引数チェック
		if (empty($dir) || empty($extension)) {
			return false;
		}

		// 再帰的に検索
		$result = self::recursivecSandir($dir);

		// ファイルリスト(戻り値)
		$result2 = array();

		// 拡張子でフィルタリング
		foreach ($result as $file) {
			if ($extension === self::getExtention($file)) {
				$result2[] = $file;
			}
		}

		// ファイルリストを返却
		return $result2;
	}

	/**
	 * 指定したファイルの拡張子を返却します。
	 *
	 * @param  unknown $file ファイルパス名
	 * @return boolean|mixed 拡張子
	 */
	public static function getExtention ($file) {

		CakeLog::debug('FileUtil::getExtentention');
		CakeLog::debug('  $file：[' . $file . ']');

		// 引数チェック
		if (empty($file)) {
			return false;
		}

		// 拡張子を取得し、返却
		return pathinfo($file, PATHINFO_EXTENSION);
	}

	/**
	 * 差が存在するか判定します。
	 *
	 * @param  unknown $file1    ファイルパス名1
	 * @param  unknown $file2    ファイルパス名2
	 * @param  unknown $exclude1 除外文字列1
	 * @param  unknown $exclude2 除外文字列2
	 * @return boolean           true：差がある、false：差がない
	 */
	public static function hasDiff($file1, $file2, $exclude1, $exclude2) {

		CakeLog::debug('FileUtil::hasDiff');
		CakeLog::debug('  $file1：[' . $file1 . ']');
		CakeLog::debug('  $file2：[' . $file2 . ']');

//		// 更新日時
//		$time1 = FileUtil::filetime($file1);
//		$time2 = FileUtil::filetime($file2);
//		if ($time1 != $time2) {
//			return true; // 差がある
//		}

//		// ファイルサイズ
//		$size1 = FileUtil::size($file1);
//		$size2 = FileUtil::size($file2);
//		if ($size1 != $size2) {
//			return true; // 差がある
//		}

		// ハッシュ
		$hash1 = FileUtil::hash($file1, $exclude1);
		$hash2 = FileUtil::hash($file2, $exclude2);
		if ($hash1 != $hash2) {
			return true; // 差がある
		}

		CakeLog::debug('no diff');
		return false; // 差がない
	}

	/**
	 * 差が存在するか判定します。
	 *
	 * @param  unknown $dir1     フォルダパス名1
	 * @param  unknown $dir2     フォルダパス名2
	 * @param  unknown $exclude1 除外文字列1
	 * @param  unknown $exclude2 除外文字列2
	 * @return boolean           true：差がある、false：差がない
	 */
	public static function hasDiffDir($dir1, $dir2, $exclude1, $exclude2) {

		CakeLog::debug('FileUtil::hasDiffDir');
		CakeLog::debug('  $dir1：[' . $dir1 . ']');
		CakeLog::debug('  $dir2：[' . $dir2 . ']');

		$file_list1 = FileUtil::getFileListFromDirAndRemoveString($dir1, $dir1);
		$file_list2 = FileUtil::getFileListFromDirAndRemoveString($dir2, $dir2);

		$count1 = count($file_list1);
		$count2 = count($file_list2);

		if ($count1 != $count2) {
			return true; // 差がある
		}

		foreach ($file_list1 as $file) {

			// 対応するファイルが存在しない場合
			if (FileUtil::exists($dir2 . DS .$file) === false) {
				return true; // 差がある
			}

			// ファイルの内容を比較
			if (FileUtil::hasDiff($dir1 . DS . $file, $dir2 . DS .$file, $exclude1, $exclude2)) {
				return true; // 差がある
			}
		}

		return false; // 差がない
	}

}

?>