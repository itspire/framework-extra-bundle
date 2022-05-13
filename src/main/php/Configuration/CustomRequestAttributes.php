<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Configuration;

class CustomRequestAttributes
{
    public const ROUTE_CALLED = 'itspire_framework_extra.route_called';
    public const CONSUMES_PROCESSED = 'itspire_framework_extra.consumes_processed';
    public const PRODUCES_PROCESSED = 'itspire_framework_extra.produces_processed';
    public const BODYPARAM_PROCESSED = 'itspire_framework_extra.bodyparam_processed';

    public const REQUEST_DESERIALIZATION_GROUPS = 'itspire_framework_extra.request_deserialization_groups';

    public const RESPONSE_CONTENT_TYPE = 'itspire_framework_extra.response_content_type';
    public const RESPONSE_FORMAT = 'itspire_framework_extra.response_format';
    public const RESPONSE_SERIALIZATION_GROUPS = 'itspire_framework_extra.response_serialization_groups';
    public const RESPONSE_STATUS_CODE = 'itspire_framework_extra.response_status_code';
}
