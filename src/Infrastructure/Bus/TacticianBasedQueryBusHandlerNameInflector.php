<?php

namespace SaaSFormation\Framework\Projects\Infrastructure\Bus;

use League\Tactician\Handler\MethodNameInflector\MethodNameInflector;

class TacticianBasedQueryBusHandlerNameInflector implements MethodNameInflector
{

    public function inflect($command, $commandHandler)
    {
        return 'ask';
    }
}