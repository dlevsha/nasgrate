<?php

namespace Util;

class Console
{
    const
        // terminal colors
        COLOR_BLACK = '30',
        COLOR_RED = '31',
        COLOR_GREEN = '32',
        COLOR_BROWN = '33',
        COLOR_BLUE = '34',
        COLOR_PURPLE = '35',
        COLOR_CYAN = '36',
        COLOR_WHITE = '37',

        DEFAULT_MESSAGE_COLOR = '',
        TERMINAL_SYMBOL_REPEAT = 50,
        TERMINAL_SYMBOL = '-';


    protected static $_instance = null;

    /**
     * @return Console
     */

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    public function write($text, $color = null, $endOfString = "\n")
    {
        $color = $color ?: self::DEFAULT_MESSAGE_COLOR;
        return print $color ? "\033[" . $color . "m" . $text . "\033[37m" . $endOfString : $text . $endOfString;
    }

    public function line($color = null)
    {
        $this->write("\n" . str_repeat(self::TERMINAL_SYMBOL, self::TERMINAL_SYMBOL_REPEAT) . "\n", $color);
    }

    public function emptyLine()
    {
        $this->write("\n");
    }

    public function writeHeader($header, $color = null)
    {
        $color = $color ? $color : self::DEFAULT_MESSAGE_COLOR;
        $this->line($color);
        $this->write($header, $color);
        $this->line($color);
    }

    public function writeError($error)
    {
        $color = self::COLOR_RED;
        $this->line($color);
        $this->write($error, $color);
        $this->line($color);
        exit;
    }

    public function writeFile($filePath)
    {
        $color = null;
        $filePathFull = DIR_ROOT . '/' . $filePath;
        if (!file_exists($filePathFull)) $this->writeError($filePathFull . ' is not readable');
        foreach (file($filePathFull) as $str) {
            $this->write($str, $color, "");
        }
    }
}