<?php

namespace App\Helpers;

class TextHelper
{
    public static function firstCharsIgnoring($sentence, array $ignoreWords = ['and', 'the'])
    {
        return collect(explode(' ', $sentence))
            ->reject(function ($word) use ($ignoreWords) {
                return in_array(strtolower($word), $ignoreWords);
            })
            ->map(function ($word) {
                return substr($word, 0, 1);
            })
            ->implode('');
    }

    public static function generateCode($name)
    {
        //  CONVERT NAME TO UPPERCASE
        // TAKE FIRST TWO CHARACTERS AND LAST TWO CHARACTERS
        // CONCATENATE THEM
        // ADD _ TO THE MIDDLE
        // RETURN THE RESULT
        if (! $name) {
            throw new \Exception('Name not provided');
        }
        if (strlen($name) < 4) {
            throw new \Exception('Name must be at least 4 characters long');
        }
        $name = strtoupper($name);
        $firstTwoChars = substr($name, 0, 2);
        $lastTwoChars = substr($name, -2);

        return $firstTwoChars.'_'.$lastTwoChars;
    }

    public static function generateRandomName()
    {
        $adjectives = ['Happy', 'Smart', 'Brave', 'Kind', 'Wise', 'Quick', 'Bright', 'Calm', 'Eager', 'Fair'];
        $nouns = ['User', 'Person', 'Member', 'Guest', 'Visitor', 'Client', 'Customer', 'Friend', 'Partner', 'Helper'];

        $adjective = $adjectives[array_rand($adjectives)];
        $noun = $nouns[array_rand($nouns)];
        $number = rand(100, 999);

        return $adjective.$noun.$number;
    }
}
