<?php

if ($ready) {
    while ($ready) {
        break;
    }
}

$ready = true;
/** @mago-expect lint:phpinnacle/blank-line-before-control-structure */
if ($ready) {}
/** @mago-expect lint:phpinnacle/blank-line-before-control-structure */
while ($ready) {
    break;
}
/** @mago-expect lint:phpinnacle/blank-line-before-control-structure */
for ($index = 0; $index < 1; ++$index) {}
/** @mago-expect lint:phpinnacle/blank-line-before-control-structure */
foreach ([] as $item) {}
/** @mago-expect lint:phpinnacle/blank-line-before-control-structure */
do {} while (false);
/** @mago-expect lint:phpinnacle/blank-line-before-control-structure */
switch ($ready) {
    default:
        break;
}
/** @mago-expect lint:phpinnacle/blank-line-before-control-structure */
try {} catch (RuntimeException $exception) {} finally {}

if ($ready) {} elseif ($ready) {} else {}

$text = 'if ($ready) { while ($ready) {} }';
$value = match ($ready) {
    true => 1,
    false => 0,
};
