<?php

namespace Angle\FileStorageBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AngleFileStorageBundle extends Bundle
{
    public function getPath(): string
    {
        return __DIR__;
    }
}