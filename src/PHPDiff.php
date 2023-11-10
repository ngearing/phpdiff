<?php

namespace NG\PHPDiff;

use Banago\PHPloy\PHPloy;
use Banago\PHPloy\Options;

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

        $this->server = $this->servers[$this->server];

        $this->connect($this->server);

        // Checkout revision from server.
        $remoteRevision = null;
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

        // Output diff results to console.
        foreach ($diffs as $filename => $result) {
            switch ($result['status']) {
                case 'modified':
                    echo "File:   {$filename}\n";
                    echo "Status: Modified.\n";
                    break;
                    case 'deleted':
                    echo "File:   {$filename}\n";
                    echo "Status: Deleted.\n";
                    break;
                default:
                    continue 2;
                    }
            
            echo "\nLocal:\n".trim($result['local'])."\n";
            echo "\nRemote:\n".trim($result['remote'])."\n";
            echo "\n-------------------------------\n";
            }
    }

    public function compareFilesWithRemote() {
        $diff = [];
        $localFiles = $this->getLocalFiles();
        $remoteFiles = $this->getRemoteFiles();



        return $diff;
    }

    public function getLocalFiles() {
        $files = [];
        return $files;
    }
    public function getRemoteFiles() {
        $files = [];
        return $files;        
    }
}
