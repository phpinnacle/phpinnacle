<?php

namespace PHPinnacle\Langcat\Enum;

enum DocumentTemplateType: string
{
    case Student = 'student';
    case Certificate = 'certificate';
    case Teacher = 'teacher';
    case ContractOfMandate = 'contractOfMandate';
    case Other = 'other';
    case Company = 'company';
    case TeacherBill = 'teacherBill';
}
