<?php

namespace NG\PHPDiff;

use Banago\PHPloy\PHPloy;
use Banago\PHPloy\Options;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class PHPDiff extends PHPloy {
    public function __construct()
    {
        $this->opt = new Options(new \League\CLImate\CLImate());
        $this->cli = $this->opt->cli;

        // Setup PHPloy, check cli flags
        $this->setup();

        if (file_exists("$this->repo/.git")) {
            $this->git = new \Banago\PHPloy\Git($this->repo);
        } else {
            throw new \Exception("'{$this->repo}' is not a Git repository.");
        }

        if (! isset($this->server) || $this->server === '') {
            throw new \Exception("Please set a server to check.");
        }

        $this->prepareServers();

        // Exit with an error if the specified server does not exist in phploy.ini
        if ($this->server != '' && !array_key_exists($this->server, $this->servers)) {
            throw new \Exception("The server \"{$this->server}\" is not defined in {$this->iniFileName}.");
        }

        $this->currentServerName = $this->server;
        if ( isset( $this->servers[$this->server] )) {
            $this->server = $this->servers[$this->currentServerName];
        }

        $this->connect($this->server);

        // Checkout revision from server.
        $remoteRevision = $this->revision;
        $this->dotRevision = $this->dotRevisionFileName;
        if ($this->connection->has($this->dotRevision)) {
            $remoteRevision = $this->connection->read($this->dotRevision);
            $this->debug('Remote revision: <bold>'.$remoteRevision);
        } else {
            throw new \Exception('No revision found on remote server...');
        }
        $output = $this->git->checkout($remoteRevision);

        if (isset($output[0])) {
            if (strpos($output[0], 'error') === 0) {
                throw new \Exception('Stash your modifications before deploying.');
            }
        }

        if (isset($output[1])) {
            if ($output[1][0] === 'M') {
                throw new \Exception('Stash your modifications before deploying.');
            }
        }

        if (isset($output[0])) {
            $this->cli->out($output[0]);
        }

        // Diff files between local & server.
        $diffs = $this->compareFilesWithRemote();
        if ( $diffs ) {
            $this->cli->bold()->yellow('Modified files have been found on the server!!!');
            foreach( $diffs as $diff ) {
                $this->cli->bold()->out(' - '.$diff['path'].($diff['new']?' *NEW* ':''));
            }

            $this->git->checkout($this->git->branch);
            exit(1);
        }

    }

    public function compareFilesWithRemote() {
        $this->globalFilesToExclude[] = $this->dotRevisionFileName;
        $diff = [];
        $localFiles = $this->getLocalFiles();
        $remoteFiles = $this->getRemoteFiles();

        // Check remote for modified or untracked files.
        foreach($remoteFiles as $rPath => $rFile) {
            $rFile['modified'] = false;
            $rFile['new'] = false;

            if (isset($localFiles[$rPath])) {
                if (
                    $rFile['size'] !== $localFiles[$rPath]['size'] && 
                    $rFile['timestamp'] > $localFiles[$rPath]['timestamp']) {
                    $rFile['modified'] = true;
                    $diff[] = $rFile;
                }
            } else {
                $rFile['modified'] = true;
                $rFile['new'] = true;
                $diff[] = $rFile;
            }
        }

        return $diff;
    }

    public function getLocalFiles() {
        $adapter = new Local($this->repo);
        $filesystem = new Filesystem($adapter);
        $files = $filesystem->listContents('', true);
        $files = $this->filterIgnoredFiles( $files );

        return array_combine(array_column($files,'path'), $files);
    }
    public function getRemoteFiles() {
        $files = $this->connection->listContents('', true);
        $files = $this->filterIgnoredFiles( $files );

        return array_combine(array_column($files,'path'), $files);
    }

    public function filterIgnoredFiles(array $files ) : array {

        foreach($files as $i => $file) {
            foreach($this->filesToExclude[$this->currentServerName] as $pattern) {
                if (pattern_match($pattern, $file['path'])) {
                    unset($files[$i]);
                    break;
                }
            }
        }

        return $files;
    }
}
