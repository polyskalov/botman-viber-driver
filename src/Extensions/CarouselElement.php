<?php

namespace TheArdent\Drivers\Viber\Extensions;

use Illuminate\Contracts\Support\Arrayable;

class CarouselElement implements Arrayable
{
    const COLUMNS = 6;
    const ROWS = 3;
    const ACTION_TYPE = 'reply';

    public $columns;
    public $rows;
    protected $text;
    protected $type;

    public static function create(string $text, int $columns = self::COLUMNS, int $rows = self::ROWS)
    {
        return new self($text, $columns, $rows);
    }

    public function __construct($text, $columns, $rows)
    {
        $this->text = $text;
        $this->columns = $columns;
        $this->rows = $rows;
        $this->type = self::ACTION_TYPE;
    }

    public function type(string $type)
    {
        $this->type = $type;
    }


    public function toArray()
    {
        return [
            'Columns' => $this->columns,
            'Rows' => $this->rows,
            'Text' => $this->text,
            'ActionType' => $this->type,
            'TextSize' => 'small',
            'ActionBody' => '#',
            'TextVAlign' => 'middle',
            'TextHAlign' => 'middle',
            'BgColor' => '#675AAA'
        ];
    }
}