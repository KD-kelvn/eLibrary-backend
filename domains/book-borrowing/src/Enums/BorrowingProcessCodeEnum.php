<?php

namespace Modules\BookBorrowing\Enums;

enum BorrowingProcessCodeEnum: string
{
    case Created = 'BRRW_CRTD';
    case Approved = 'BRRW_APPRV';
    case Rejected = 'BRRW_RJCT';
    case ReturnInitiated = 'BRRW_RTRN';
    case BookReturned = 'BRRW_RTRND';
}
