<?php

namespace SaaSFormation\Framework\Projects\Infrastructure;

use SaaSFormation\Framework\Contracts\Infrastructure\EnvVarsManagerInterface;

class MySQLClientProvider
{
    public function __construct(private EnvVarsManagerInterface $envVarsManager)
    {
    }

    public function provide(): \PDO
    {
        $mysqlUri = $this->envVarsManager->get('MYSQL_URI');
        $mysqlUsername = $this->envVarsManager->get('MYSQL_USERNAME');
        $mysqlPassword = $this->envVarsManager->get('MYSQL_PASSWORD');

        if(!is_string($mysqlUri)) {
            throw new \InvalidArgumentException('MYSQL_URI must be a string');
        }

        if(!is_string($mysqlUsername)) {
            throw new \InvalidArgumentException('MYSQL_USERNAME must be a string');
        }

        if(!is_string($mysqlPassword)) {
            throw new \InvalidArgumentException('MYSQL_PASSWORD must be a string');
        }

        $pdo = new \PDO($mysqlUri, $mysqlUsername, $mysqlPassword);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}