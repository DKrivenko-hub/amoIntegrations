<?php 

namespace AmoIntegrations\Enums;

enum ENotesTypes:string{
    case Common='common';
    case CallIn='call_in';
    case CallOut='call_out';
    case ServiceMessage='service_message';
    case MessageCashier='message_cashier';
    case InvoicePaid='invoice_paid';
    case Geolocation='geolocation';
    case SmsIn	='sms_in';
    case SmsOut='sms_out';
    case ExtendedServiceMessage='extended_service_message';

}