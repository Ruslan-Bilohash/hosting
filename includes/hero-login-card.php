<?php
declare(strict_types=1);

/** @param array<string, string> $t */
function hs_render_hero_login_card(array $t): string
{
    hs_session_start();
    $registerLabel = (string) ($t['hero_login_register'] ?? $t['nav_register'] ?? '');

    $html = '<div class="hs-hero-login">';
    $html .= '<form class="hs-hero-login-form" method="post" action="' . hs_h(hs_url('login.php')) . '" id="cabinet-login">';
    $html .= hs_csrf_field();
    $html .= '<div class="hs-field">';
    $html .= '<label for="hero-login-user">' . hs_h($t['login_email'] ?? $t['login_user'] ?? 'Email or username') . '</label>';
    $html .= '<input type="text" id="hero-login-user" name="login" required autocomplete="username" placeholder="' . hs_h($t['hero_login_user_ph'] ?? 'you@company.com') . '">';
    $html .= '</div>';
    $html .= '<div class="hs-field">';
    $html .= '<label for="hero-login-pass">' . hs_h($t['login_password'] ?? 'Password') . '</label>';
    $html .= '<input type="password" id="hero-login-pass" name="password" required autocomplete="current-password" value="">';
    $html .= '</div>';
    $html .= '<button type="submit" class="hs-btn hs-btn-primary hs-hero-login-submit">';
    $html .= '<i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> ';
    $html .= hs_h($t['hero_login_submit'] ?? $t['login_submit'] ?? $t['nav_login'] ?? 'Log in');
    $html .= '</button>';
    $html .= '</form>';
    if ($registerLabel !== '') {
        $html .= '<p class="hs-hero-login-footer">';
        $html .= hs_h($t['hero_login_no_account'] ?? 'No account yet?') . ' ';
        $html .= '<a href="' . hs_h(hs_url('register.php')) . '">' . hs_h($registerLabel) . '</a>';
        $html .= '</p>';
    }
    $html .= '</div>';

    return $html;
}
