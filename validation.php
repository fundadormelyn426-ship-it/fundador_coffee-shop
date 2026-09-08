<?php

/**
 * Validate customer name.
 *
 * Allows:
 * - Letters
 * - Spaces
 * - Apostrophes
 * - Hyphens
 * - Periods
 *
 * Length: 2–100 characters.
 */
function valid_name($name)
{
    return (bool) preg_match(
        "/^[\p{L} .'-]{2,100}$/u",
        trim($name)
    );
}


/**
 * Validate email address.
 */
function valid_email($email)
{
    return filter_var(
        trim($email),
        FILTER_VALIDATE_EMAIL
    ) !== false;
}


/**
 * Validate password.
 *
 * Minimum: 6 characters.
 */
function valid_password($password)
{
    return is_string($password)
        && strlen($password) >= 6;
}


/**
 * Check whether required POST fields are present.
 */
function require_post($fields)
{
    foreach ($fields as $field) {

        if (
            !isset($_POST[$field]) ||
            trim((string) $_POST[$field]) === ''
        ) {
            return false;
        }
    }

    return true;
}


/**
 * Clean and limit text.
 *
 * Default maximum length: 255 characters.
 */
function clean_text($value, $max = 255)
{
    return mb_substr(
        trim((string) $value),
        0,
        $max
    );
}