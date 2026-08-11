<?php

namespace LeanCaptain\Money;

enum RoundingMode
{
    case REJECT;
    case HALF_UP;
    case DOWN;
}
