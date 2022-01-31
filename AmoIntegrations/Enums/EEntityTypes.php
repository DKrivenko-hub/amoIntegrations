<?php 

namespace AmoIntegrations\Enums;

enum EEntityTypes :string
{
    case Contacts = 'contacts';
    case Leads = 'leads';
    case Companies = 'companies';
    case Customers = 'customers';
}