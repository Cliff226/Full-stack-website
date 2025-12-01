<?php
// Content Security Policy (CSP)

// Anti-clickjacking
header('X-Frame-Options: SAMEORIGIN');

// Prevent MIME-type sniffing
header('X-Content-Type-Options: nosniff');

