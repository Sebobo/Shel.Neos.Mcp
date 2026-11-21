<?php
namespace Neos\Flow\Core\Migrations;

/**
 * Adjusts this package to the fork of Sitegeist.SlopMachine that is named Shel.Neos.Mcp:
 * moves the settings of the package and renames its package key, class names and table names.
 *
 * Run it for every project package that references the old package, for example:
 * ./flow core:migrate --package-key Acme.Site
 */
class Version20261121120001 extends AbstractMigration
{
    public function getIdentifier()
    {
        return 'Shel.Neos.Mcp-20261121120001';
    }

    /**
     * @return void
     */
    public function up()
    {
        $filter = ['php', 'yaml', 'yml', 'fusion', 'xlf', 'html', 'md'];

        $this->moveSettingsPaths('Sitegeist.SlopMachine', 'Shel.Neos.Mcp');

        $this->searchAndReplace('Sitegeist\\SlopMachine', 'Shel\\Neos\\Mcp', $filter);
        $this->searchAndReplace('Sitegeist\\\\SlopMachine', 'Shel\\\\Neos\\\\Mcp', $filter);
        $this->searchAndReplace('Sitegeist.SlopMachine', 'Shel.Neos.Mcp', $filter);
        $this->searchAndReplace('sitegeist_slopmachine', 'shel_neos_mcp', $filter);
        $this->searchAndReplace('sitegeist/slopmachine', 'shel/neos-mcp', $filter);

        $this->showWarning('Replace "sitegeist/slopmachine" with "shel/neos-mcp" in the composer.json of the project and every package and run "composer update" - core migrations do not touch composer manifests.');
        $this->showNote('Run "./flow doctrine:migrate": the migration Version20261121120000 of the package renames the tables sitegeist_slopmachine_* to shel_neos_mcp_* or creates them.');
        $this->showNote('Role identifiers stored in the database (role assignments of the security accounts, the role names of the authentication tokens) still name "Sitegeist.SlopMachine:McpAgent*" and must be adjusted by hand.');
        $this->showNote('A Settings file that was rewritten by the move of the settings paths loses its comments - check it with "git diff" before deploying.');
    }
}
