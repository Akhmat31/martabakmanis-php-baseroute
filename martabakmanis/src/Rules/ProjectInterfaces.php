<?php

namespace RequestProjectInterfaces;

interface RequestProjectInterfaces {
    public function process(mixed $request, string $handler): mixed;
}