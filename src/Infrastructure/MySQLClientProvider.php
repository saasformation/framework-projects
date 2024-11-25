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

        if(!is_string($mysqlUri)) {
            throw new \InvalidArgumentException('MYSQL_URI must be a string');
        }

        $pdo = new \PDO($mysqlUri);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}