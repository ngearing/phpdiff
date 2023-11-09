<?php

namespace NG\PHPDiff;

use Banago\PHPloy\PHPloy;
use Banago\PHPloy\Options;

class PHPDiff extends PHPloy {
    public function __construct()
    {
        $this->opt = new Options(new \League\CLImate\CLImate());
        $this->cli = $this->opt->cli;

        // $this->cli->backgroundGreen()->bold()->out('-------------------------------------------------');
        // $this->cli->backgroundGreen()->bold()->out('|                     PHPloy                    |');
        // $this->cli->backgroundGreen()->bold()->out('-------------------------------------------------');

        if ($this->cli->arguments->defined('dryrun')) {
            $this->cli->bold()->yellow('DRY RUN, PHPloy will not check or alter the remote servers');
        }

        // Setup PHPloy
        $this->setup();

        // Check if only valid arguments are given
        // @Todo: Breaks this format: --sync="asdfasdfads"
        $arg = $this->checkArguments();
        if ($arg) {
            $this->cli->bold()->error("Argument '{$arg}' is unknown.");
            $this->cli->usage();

            return;
        };

        if ($this->cli->arguments->get('help')) {
            $this->cli->usage();

            return;
        }

        if ($this->cli->arguments->get('init')) {
            $this->createIniFile();

            return;
        }

        if ($this->cli->arguments->get('version')) {
            $this->cli->bold()->info('PHPloy v'.$this->version);

            return;
        }

        if ($this->cli->arguments->get('dryrun')) {
            $this->cli->bold()->yellow('DRY RUN, PHPloy will not go any further');

            return;
        }

        if (file_exists("$this->repo/.git")) {
            $this->git = new \Banago\PHPloy\Git($this->repo);
            // $this->deploy();
        } else {
            throw new \Exception("'{$this->repo}' is not a Git repository.");
        }
    }
}
