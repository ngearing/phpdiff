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

        if (! isset($this->server)) {
            throw new \Exception("Please set a server to check.");
        }

        $this->prepareServers();

        // Exit with an error if the specified server does not exist in phploy.ini
        if ($this->server != '' && !array_key_exists($this->server, $this->servers)) {
            throw new \Exception("The server \"{$this->server}\" is not defined in {$this->iniFileName}.");
        }

        $this->currentServerName = $this->server;
        $this->server = $this->servers[$this->server];

        $this->connect($this->server);

        // Checkout revision from server.
        $remoteRevision = $this->revision;
        if ($this->connection->has($this->dotRevision)) {
            $remoteRevision = $this->connection->read($this->dotRevision);
            $this->debug('Remote revision: <bold>'.$remoteRevision);
        } else {
            $this->cli->out('No revision found. Fresh upload...');
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


    }

    public function compareFilesWithRemote() {
        $diff = [];
        $localFiles = $this->getLocalFiles();
        $remoteFiles = $this->getRemoteFiles();



        return $diff;
    }

    public function getLocalFiles() {
        $adapter = new Local($this->repo);
        $filesystem = new Filesystem($adapter);
        $files = $filesystem->listContents('', true);
        $files = $this->filterIgnoredFiles( $files );

        return $files;
    }
    public function getRemoteFiles() {
        $files = $this->connection->listContents('', true);
        return $files;        
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
