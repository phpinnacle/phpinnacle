<?php

namespace PHPinnacle\Tempo\Forms;

class TimePicker extends DatePicker
{
    public function setUp(): void
    {
        parent::setUp();

        $this->time();
    }
}
