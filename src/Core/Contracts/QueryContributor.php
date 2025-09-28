<?php
namespace atc\WHx4\Core\Contracts;

interface QueryContributor
{
    /** @param array<string,mixed> $args @param array<string,mixed> $params */
    public function adjustQueryArgs(array $args, array $params): array;
}
