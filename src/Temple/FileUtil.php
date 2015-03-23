<?php

namespace Temple;

class FileUtil
{
    /**
     * Deletes local folder (recursively)
     *
     * @param $path
     * @throws \Exception
     */
    public static function deleteFolder($path)
    {
        if (!is_dir($path)) {
            throw new \Exception("$path is not a directory");
        }
        if (substr($path, strlen($path) - 1, 1) != '/') {
            $path .= '/';
        }
        $dotfiles = glob($path . '.*', GLOB_MARK);
        $files = glob($path . '*', GLOB_MARK);
        $files = array_merge($files, $dotfiles);
        foreach ($files as $file) {
            if (basename($file) == '.' || basename($file) == '..') {
                continue;
            } else {
                if (is_dir($file)) {
                    self::deleteFolder($file);
                } else {
                    unlink($file);
                }
            }
        }
        rmdir($path);
    }

    /**
     * Copies local folder (recursively)
     *
     * @param $src
     * @param $dst
     * @return bool
     */
	public static function copyFolder($src, $dst)
	{
		$output = false;

		$dir = opendir($src);
		if (@mkdir($dst)) {
			while (false !== ($file = readdir($dir))) {
				if (($file != '.') && ($file != '..')) {
					if (is_dir($src . '/' . $file)) {
						if (!self::copyFolder($src . '/' . $file, $dst . '/' . $file)) {
							break;
						}
					} else {
						copy($src . '/' . $file, $dst . '/' . $file);
					}
				}
			}
			$output = true;
		}
		closedir($dir);

		return $output;
	}

    /**
     * Adds local folder (recursively) to the ZIP archive
     *
     * @param $path
     * @param \ZipArchive $zip
     * @param null $baseDir
     * @param int $count
     * @param string $zipFilename
     * @return mixed
     */
    public static function addZipDir($path, &$zip, $baseDir = null, $count = 0, $zipFilename = '')
    {
        if ($baseDir == null) {
            $baseDir = $path;
        }

        $zip->addEmptyDir(str_replace($baseDir, '', $path));
        $nodes = glob($path . '/*');
        foreach ($nodes as $node) {
            if (($count % 100 == 0 || memory_get_usage(true) >= 100000000) && $zipFilename != '') {
                $zip->close();
                $zip->open($zipFilename, \ZipArchive::CREATE);
            }
            if (is_dir($node)) {
                self::addZipDir($node, $zip, $baseDir, $count, $zipFilename);
            } else {
                if (is_file($node)) {
                    $zip->addFile($node, str_replace($baseDir, '', $node));
                }
            }
            $count++;
        }

        return $zip;
    }

    /**
     * Uploads file (or folder) to a remove directory using active FTP connection
     *
     * @param $ftpConnectionHandle (get it with ftp_connect and ftp_login)
     * @param $file
     * @param $remoteDir
     */
    public static function uploadFile($ftpConnectionHandle, $file, $remoteDir)
    {
        // it makes no sense to continue if provided path does not exist locally
        if (!file_exists($file)) {
            return;
        }

        // change to remote dir if path is explicitly given
        $initialRemoteDir = ftp_pwd($ftpConnectionHandle);
        if ($remoteDir != '' && $remoteDir != $initialRemoteDir) {
            if (!ftp_chdir($ftpConnectionHandle, $remoteDir)) {
                return;
            }
        }

        // if provided file is a directory, delegate the task to a better suited function
        if (is_dir($file)) {
            self::uploadDir($ftpConnectionHandle, $file, $remoteDir);
            return;
        } else {
            // upload the file and provide good permissions
            ftp_put($ftpConnectionHandle, basename($file), realpath($file), FTP_ASCII);
            ftp_chmod($ftpConnectionHandle, 0777, basename($file));
        }

        // restore initial directory
        ftp_chdir($ftpConnectionHandle, $initialRemoteDir);
    }

    /**
     * Uploads folder (recursively) to a remote directory using active FTP connection
     *
     * @param $ftpConnectionHandle (get it with ftp_connect and ftp_login)
     * @param $dir
     * @param string $remoteDir
     */
    public static function uploadDir($ftpConnectionHandle, $dir, $remoteDir = '')
    {
        // it makes no sense to continue if provided path does not exist locally
        if (!file_exists($dir)) {
            return;
        }

        // change to remote dir if path is explicitly given
        $initialRemoteDir = ftp_pwd($ftpConnectionHandle);

        if ($remoteDir != '' && $remoteDir != $initialRemoteDir) {
            if (!ftp_chdir($ftpConnectionHandle, $remoteDir)) {
                return;
            }
        }

        $nodes = glob($dir . '/*');
        foreach ($nodes as $node) {
            if (is_dir($node)) {
                if (ftp_mkdir($ftpConnectionHandle, basename($node))) {
                    if (ftp_chmod($ftpConnectionHandle, 0777, basename($node))) {
                        self::uploadDir($ftpConnectionHandle, $node, $remoteDir . '/' . basename($node));
                    }
                }
            } else {
                @ftp_put($ftpConnectionHandle, basename($node), realpath($node), FTP_ASCII);
                ftp_chmod($ftpConnectionHandle, 0777, basename($node));
            }
        }

        // restore initial directory
        ftp_chdir($ftpConnectionHandle, $initialRemoteDir);
    }

    /**
     * Deletes remote node on the active FTP connection (recursively)
     *
     * @param $ftpConnectionHandle (as received with ftp_connect and ftp_login)
     * @param $nodePath
     */
    public static function deleteRemoteNode($ftpConnectionHandle, $nodePath)
    {
        $initialDir = ftp_pwd($ftpConnectionHandle);
        if (@ftp_chdir($ftpConnectionHandle, $nodePath)) {
            // given $nodePath is a folder
            foreach(ftp_nlist($ftpConnectionHandle, $nodePath) as $childNodeName) {
                if (!in_array($childNodeName, array('.', '..'))) {
                    self::deleteRemoteNode($ftpConnectionHandle, $nodePath . '/' . $childNodeName);
                }
            }
            ftp_chdir($ftpConnectionHandle, $initialDir);
            ftp_rmdir($ftpConnectionHandle, $nodePath);
        } else {
            // given $nodePath is a file or does not exist
            @ftp_delete($ftpConnectionHandle, $nodePath);
        }
    }

}
