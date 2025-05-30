<?php

declare(strict_types=1);

namespace openvk\Web\Util\Shell;

class Shell
{
    public static function shellAvailable(): bool
    {
        if (ini_get("safe_mode")) {
            return false;
        }

        $functions = array_map(function ($x) {
            return trim($x);
        }, explode(" ", ini_get("disable_functions")));
        if (in_array("system", $functions)) {
            return false;
        }

        if (Shell::isPowershell()) {
            exec("WHERE powershell", $_x, $_c);
            unset($_x);

            return $_c === 0;
        }

        return true;
    }

    public static function isPowershell(): bool
    {
        return strncasecmp(PHP_OS, 'WIN', 3) === 0;
    }

    public static function commandAvailable(string $name): bool
    {
        if (!Shell::shellAvailable()) {
            throw new Exceptions\ShellUnavailableException();
        }

        if (Shell::isPowershell()) {
            exec("WHERE $name", $_x, $_c);
            unset($_x);

            return $_c === 0;
        }

        return !is_null(`command -v $name`);
    }

    public static function __callStatic(string $name, array $arguments): object
    {
        if (!Shell::commandAvailable($name)) {
            throw new Exceptions\UnknownCommandException($name);
        }

        $command = implode(" ", array_merge([$name], $arguments));

        return new class ($command) {
            private $command;

            public function __construct(string $cmd)
            {
                $this->command = $cmd;
            }

            public function execute(?int &$result = nullptr): string
            {
                $stdout = [];

                if (Shell::isPowershell()) {
                    $cmd = escapeshellarg($this->command);
                    exec("powershell -Command $this->command", $stdout, $result);
                } else {
                    exec($this->command, $stdout, $result);
                }

                return implode(PHP_EOL, $stdout);
            }

            public function start(): string
            {
                if (Shell::isPowershell()) {
                    $cmd = escapeshellarg($this->command);
                    pclose(popen("start /b powershell -Command $this->command", "r"));
                } else {
                    system("nohup " . $this->command . " > /dev/null 2>/dev/null &");
                }

                return $this->command;
            }
        };
    }
}
