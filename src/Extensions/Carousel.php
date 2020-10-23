<?php

namespace TheArdent\Drivers\Viber\Extensions;

use Illuminate\Contracts\Support\Arrayable;

class Carousel implements Arrayable
{
    const GROUP_COLUMNS = 6;
    const GROUP_ROWS = 3;
    const MIN_API_VERSION = 2;
    const BG_COLOR = '#FFFFFF';

    protected $elements = [];
    protected $groupColumns;
    protected $groupRows;
    protected $bgColor;

    public static function create(int $columns = self::GROUP_COLUMNS, int $rows = self::GROUP_ROWS, string $color = self::BG_COLOR)
    {
        return new self($columns, $rows, $color);
    }

    public function __construct($columns, $rows, $color)
    {
        $this->groupColumns = $columns;
        $this->groupRows = $rows;
        $this->bgColor = $color;
    }

    /**
     * @param CarouselElement ...$elements
     * @return $this
     * @throws \Exception
     */
    public function addElement(CarouselElement ...$elements)
    {
        $countRows = 0;

        foreach ($elements as $element) {
            $countRows += $element->rows;

            if ($element->columns > $this->groupColumns) {
                throw new \Exception("Columns in element greater, than group columns.");
            }

        }

        if ($countRows > $this->groupRows) {
            throw new \Exception("Count rows in elements greater, than group rows.");
        }

        $this->elements[] = $elements;

        return $this;
    }

    protected function elementsToArray()
    {
        return collect($this->elements)->map(function ($coreElements) {

            return collect($coreElements)->map(function (CarouselElement $element) {
                return $element->toArray();
            })
                ->toArray();

        })
            ->flatten(1)
            ->toArray();
    }

    public function toArray()
    {
        return [
            "min_api_version" => self::MIN_API_VERSION,
            "type" => "rich_media",
            "driver_type" => 'carousel',
            "rich_media" => [
                "Type" => "rich_media",
                "ButtonsGroupColumns" => $this->groupColumns,
                "ButtonsGroupRows" => $this->groupRows,
                "Buttons" => $this->elementsToArray(),
                "BgColor" => $this->bgColor
            ]
        ];
    }
}