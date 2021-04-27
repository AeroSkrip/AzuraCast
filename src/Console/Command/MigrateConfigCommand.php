<?php

namespace App\Console\Command;

use App\Environment;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateConfigCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        Environment $environment
    ): int {
        $envSettings = [];

        $iniPath = $environment->getBaseDirectory() . '/env.ini';
        if (file_exists($iniPath)) {
            $envSettings = (array)parse_ini_file($iniPath);
        }

        // Migrate from existing legacy config files.
        $legacyIniPath = $environment->getBaseDirectory() . '/app/env.ini';
        if (file_exists($legacyIniPath)) {
            $iniSettings = parse_ini_file($legacyIniPath);
            $envSettings = array_merge($envSettings, (array)$iniSettings);
        }

        $legacyAppEnvFile = $environment->getBaseDirectory() . '/app/.env';
        if (file_exists($legacyAppEnvFile)) {
            $envSettings[Environment::APP_ENV] ??= file_get_contents($legacyAppEnvFile);
        }

        $legacyDbConfFile = $environment->getBaseDirectory() . '/app/config/db.conf.php';
        if (file_exists($legacyDbConfFile)) {
            $dbConf = include($legacyDbConfFile);

            $envSettings[Environment::DB_PASSWORD] ??= $dbConf['password'];
            if (isset($dbConf['user']) && 'root' === $dbConf['user']) {
                $envSettings[Environment::DB_USER] = 'root';
            }
        }

        // Migrate from older environment variable names to new ones.
        $settingsToMigrate = [
            'application_env' => Environment::APP_ENV,
            'db_host' => Environment::DB_HOST,
            'db_port' => Environment::DB_PORT,
            'db_name' => Environment::DB_NAME,
            'db_username' => Environment::DB_USER,
            'db_password' => Environment::DB_PASSWORD,
        ];

        foreach ($settingsToMigrate as $oldSetting => $newSetting) {
            if (!empty($envSettings[$oldSetting])) {
                $envSettings[$newSetting] ??= $envSettings[$oldSetting];
                unset($envSettings[$oldSetting]);
            }
        }

        // Set sensible defaults for variables that may not be set.
        $envSettings[Environment::DB_HOST] ??= 'localhost';
        if ('azuracast' === $envSettings[Environment::DB_HOST]) {
            $envSettings[Environment::DB_HOST] = 'localhost';
        }

        $envSettings[Environment::DB_PORT] ??= '3306';
        $envSettings[Environment::DB_NAME] ??= 'azuracast';
        $envSettings[Environment::DB_USER] ??= 'azuracast';

        $iniData = [
            ';',
            '; AzuraCast Environment Settings',
            ';',
            '; This file is automatically generated by AzuraCast.',
            ';',
            '[configuration]',
        ];
        foreach ($envSettings as $settingKey => $settingVal) {
            $iniData[] = $settingKey . '="' . $settingVal . '"';
        }

        file_put_contents($iniPath, implode("\n", $iniData));

        // Remove legacy files.
        @unlink($legacyIniPath);
        @unlink($legacyAppEnvFile);
        @unlink($legacyDbConfFile);

        $io->writeln(__('Configuration successfully written.'));
        return 0;
    }
}
