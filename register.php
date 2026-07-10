<?php
declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/client-auth.php';
require_once __DIR__ . '/includes/plans.php';
require_once __DIR__ . '/includes/domain-store.php';
require_once __DIR__ . '/includes/countries.php';

hs_seed_demo_data();

if (hs_client_id() !== null) {
    hs_redirect(hs_panel_path(''));
}

if (!empty($_GET['domain'])) {
    hs_session_start();
    $d = hs_domain_normalize((string) $_GET['domain']);
    if ($d !== null) {
        $_SESSION['hs_pending_domain'] = $d;
    }
}
$pendingDomain = '';
hs_session_start();
if (!empty($_SESSION['hs_pending_domain'])) {
    $pendingDomain = (string) $_SESSION['hs_pending_domain'];
}

$error = '';
$startStep = 1;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? '';
        $startStep = 6;
    } else {
        $res = hs_client_register([
            'name' => $_POST['name'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? '',
            'plan' => $_POST['plan'] ?? 'starter',
            'company' => $_POST['company'] ?? '',
            'vat' => $_POST['vat'] ?? '',
            'address' => $_POST['address'] ?? '',
            'city' => $_POST['city'] ?? '',
            'postal' => $_POST['postal'] ?? '',
            'country' => $_POST['country'] ?? '',
            'account_type' => $_POST['account_type'] ?? 'personal',
            'domain_wish' => $_POST['domain_wish'] ?? '',
            'consent_terms' => !empty($_POST['consent_terms']),
            'consent_privacy' => !empty($_POST['consent_privacy']),
            'consent_marketing' => !empty($_POST['consent_marketing']),
        ]);
        if ($res['ok']) {
            hs_redirect('checkout.php');
        }
        $error = $t['register_error_' . ($res['error'] ?? 'save_failed')] ?? ($res['error'] ?? '');
        $startStep = 6;
    }
}

$plans = hs_plans();
$defaultPlan = (string) ($_GET['plan'] ?? $_POST['plan'] ?? 'starter');
if (!hs_plan_id_valid($defaultPlan)) {
    $defaultPlan = 'starter';
}
$post = $_POST;
$isDemoPrefill = defined('HS_DEMO_MODE') && HS_DEMO_MODE && $_SERVER['REQUEST_METHOD'] !== 'POST';
$demoUsername = $isDemoPrefill ? 'demo' : (string) ($post['username'] ?? '');
$demoPassword = $isDemoPrefill ? 'demo' : '';
$demoMinPass = $isDemoPrefill ? 4 : 8;
$accountType = (string) ($post['account_type'] ?? 'personal');
if ($accountType !== 'business') {
    $accountType = 'personal';
}

$selCountry = strtoupper((string) ($post['country'] ?? 'NO'));
if (!hs_country_valid($selCountry)) {
    $selCountry = 'NO';
}

$steps = [
    1 => $t['register_step_plan'] ?? 'Plan',
    2 => $t['register_step_type'] ?? 'Client type',
    3 => $t['register_step_details'] ?? 'Your details',
    4 => $t['register_step_access'] ?? 'Login',
    5 => $t['register_step_address'] ?? 'Address & domain',
    6 => $t['register_step_confirm'] ?? 'Confirm',
];

$extra_footer_scripts = ['js/register-wizard.js'];
ob_start();
?>
<div class="hs-auth-wrap">
  <div class="hs-auth-card hs-register-card">
    <h1><?= hs_h($t['register_title'] ?? '') ?></h1>
    <p class="hp-muted"><?= hs_h($t['register_intro'] ?? '') ?></p>
    <?php if ($pendingDomain !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($t['register_domain_selected'] ?? 'Domain') ?>: <strong><?= hs_h($pendingDomain) ?></strong></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

    <?php
    $summaryLabels = json_encode([
        'plan' => $t['register_summary_plan'] ?? 'Plan',
        'type' => $t['register_summary_type'] ?? 'Type',
        'personal' => $t['register_type_personal'] ?? 'Personal',
        'business' => $t['register_type_business'] ?? 'Business',
        'company' => $t['register_company'] ?? 'Company',
        'vat' => $t['register_vat'] ?? 'VAT',
        'name' => $t['register_summary_name'] ?? 'Name',
        'email' => $t['register_email'] ?? 'Email',
        'phone' => $t['register_phone'] ?? 'Phone',
        'username' => $t['register_username'] ?? 'Username',
        'address' => $t['register_summary_address'] ?? 'Address',
        'domain' => $t['register_domain_wish'] ?? 'Domain',
    ], JSON_UNESCAPED_UNICODE);
    ?>
    <div class="hs-reg-wizard" data-hs-reg-wizard data-start-step="<?= (int) $startStep ?>" data-current-step="1"
      data-summary-labels="<?= hs_h($summaryLabels) ?>">
      <ol class="hs-reg-steps" aria-label="<?= hs_h($t['register_steps_label'] ?? 'Registration steps') ?>">
        <?php foreach ($steps as $num => $label): ?>
        <li class="hs-reg-step-item" data-reg-step-item="<?= (int) $num ?>">
          <span class="hs-reg-step-num"><?= (int) $num ?></span>
          <span class="hs-reg-step-label"><?= hs_h($label) ?></span>
        </li>
        <?php endforeach; ?>
      </ol>

      <form method="post" action="" class="hs-register-form" id="hs-register-form" novalidate>
        <?= hs_csrf_field() ?>

        <!-- Step 1: Plan -->
        <section class="hs-reg-panel" data-reg-step="1">
          <h2 class="hs-reg-panel-title"><i class="fa-solid fa-layer-group"></i> <?= hs_h($t['register_step_plan'] ?? 'Plan') ?></h2>
          <p class="hs-reg-panel-hint"><?= hs_h($t['register_step_plan_hint'] ?? '') ?></p>
          <div class="hs-plans hs-plans-register">
            <?php foreach ($plans as $pid => $plan): ?>
            <?php
              $desc = (string) ($t[hs_plan_desc_key($pid)] ?? '');
              $badges = '';
              if (($plan['badge'] ?? '') === 'popular') {
                  $badges .= '<span class="hs-plan-badge hs-plan-badge-popular">' . hs_h($t['plan_popular'] ?? '') . '</span>';
              }
              if ((int) ($plan['discount_pct'] ?? 0) > 0) {
                  $badges .= '<span class="hs-plan-badge hs-plan-badge-sale">' . hs_h($t['plan_discount_now'] ?? '') . '</span>';
              }
              if (($plan['badge'] ?? '') === 'vps') {
                  $badges .= '<span class="hs-plan-badge hs-plan-badge-vps">' . hs_h($t['plan_vps_badge'] ?? 'VPS') . '</span>';
              }
            ?>
            <label class="hs-plan<?= ($pid === $defaultPlan) ? ' is-selected' : '' ?>" data-hs-plan-card>
              <input type="radio" name="plan" value="<?= hs_h($pid) ?>" <?= ($pid === $defaultPlan) ? 'checked' : '' ?> required>
              <?php if ($badges !== ''): ?><div class="hs-plan-badges"><?= $badges ?></div><?php endif; ?>
              <h3><?= hs_h($t['plan_' . $pid] ?? $pid) ?></h3>
              <?php if ($desc !== ''): ?><p class="hs-plan-desc"><?= hs_h($desc) ?></p><?php endif; ?>
              <?= hs_render_plan_price_block($plan, $t, $lang) ?>
              <ul class="hs-plan-features">
                <?php foreach (hs_plan_feature_lines($plan, $t) as $line): ?>
                <li><i class="fa-solid fa-check"></i> <?= hs_h($line) ?></li>
                <?php endforeach; ?>
              </ul>
            </label>
            <?php endforeach; ?>
          </div>
        </section>

        <!-- Step 2: Client type -->
        <section class="hs-reg-panel" data-reg-step="2" hidden>
          <h2 class="hs-reg-panel-title"><i class="fa-solid fa-user-tag"></i> <?= hs_h($t['register_step_type'] ?? 'Client type') ?></h2>
          <p class="hs-reg-panel-hint"><?= hs_h($t['register_step_type_hint'] ?? '') ?></p>
          <div class="hs-reg-type-cards">
            <label class="hs-reg-type-card<?= $accountType === 'personal' ? ' is-selected' : '' ?>" data-reg-type-card>
              <input type="radio" name="account_type" value="personal" <?= $accountType !== 'business' ? 'checked' : '' ?> required>
              <span class="hs-reg-type-icon"><i class="fa-solid fa-user"></i></span>
              <span class="hs-reg-type-name"><?= hs_h($t['register_type_personal'] ?? 'Personal') ?></span>
              <span class="hs-reg-type-desc"><?= hs_h($t['register_type_personal_desc'] ?? '') ?></span>
            </label>
            <label class="hs-reg-type-card<?= $accountType === 'business' ? ' is-selected' : '' ?>" data-reg-type-card>
              <input type="radio" name="account_type" value="business" <?= $accountType === 'business' ? 'checked' : '' ?>>
              <span class="hs-reg-type-icon"><i class="fa-solid fa-building"></i></span>
              <span class="hs-reg-type-name"><?= hs_h($t['register_type_business'] ?? 'Business') ?></span>
              <span class="hs-reg-type-desc"><?= hs_h($t['register_type_business_desc'] ?? '') ?></span>
            </label>
          </div>
        </section>

        <!-- Step 3: Contact details (different per type) -->
        <section class="hs-reg-panel" data-reg-step="3" hidden>
          <h2 class="hs-reg-panel-title"><i class="fa-solid fa-address-card"></i> <?= hs_h($t['register_step_details'] ?? 'Your details') ?></h2>
          <p class="hs-reg-panel-hint" data-reg-hint-personal<?= $accountType === 'business' ? ' hidden' : '' ?>><?= hs_h($t['register_personal_hint'] ?? '') ?></p>
          <p class="hs-reg-panel-hint" data-reg-hint-business<?= $accountType !== 'business' ? ' hidden' : '' ?>><?= hs_h($t['register_business_hint'] ?? '') ?></p>

            <div class="hs-reg-fields-block" data-reg-business<?= $accountType !== 'business' ? ' hidden' : '' ?>>
              <h3 class="hs-reg-subtitle"><i class="fa-solid fa-building"></i> <?= hs_h($t['register_business_company_title'] ?? 'Company') ?></h3>
              <div class="hs-field">
                <label for="company"><?= hs_h($t['register_company'] ?? 'Company name') ?> *</label>
                <input type="text" id="company" name="company" class="hs-reg-business-only" data-reg-business-only
                  <?= $accountType === 'business' ? 'required' : '' ?>
                  value="<?= hs_h($post['company'] ?? '') ?>" <?= $accountType !== 'business' ? 'disabled' : '' ?>>
              </div>
              <div class="hs-field">
                <label for="vat"><?= hs_h($t['register_vat'] ?? 'Org / VAT') ?> *</label>
                <input type="text" id="vat" name="vat" class="hs-reg-business-only" data-reg-business-only
                  placeholder="<?= hs_h($t['register_vat_ph'] ?? 'NO 999 999 999 MVA') ?>"
                  <?= $accountType === 'business' ? 'required' : '' ?>
                  value="<?= hs_h($post['vat'] ?? '') ?>" <?= $accountType !== 'business' ? 'disabled' : '' ?>>
              </div>
              <h3 class="hs-reg-subtitle"><i class="fa-solid fa-user-tie"></i> <?= hs_h($t['register_business_contact_title'] ?? 'Contact person') ?></h3>
            </div>

            <div class="hs-reg-fields-block" data-reg-personal<?= $accountType === 'business' ? ' hidden' : '' ?>>
              <h3 class="hs-reg-subtitle"><i class="fa-solid fa-user"></i> <?= hs_h($t['register_personal_title'] ?? 'Personal details') ?></h3>
            </div>

            <div class="hp-grid-2">
              <div class="hs-field">
                <label for="first_name" data-label-personal="<?= hs_h($t['register_first_name'] ?? 'First name') ?>"
                  data-label-business="<?= hs_h($t['register_contact_first_name'] ?? 'Contact first name') ?>">
                  <?= hs_h($accountType === 'business' ? ($t['register_contact_first_name'] ?? 'Contact first name') : ($t['register_first_name'] ?? 'First name')) ?> *
                </label>
                <input type="text" id="first_name" name="first_name" required value="<?= hs_h($post['first_name'] ?? '') ?>">
              </div>
              <div class="hs-field">
                <label for="last_name" data-label-personal="<?= hs_h($t['register_last_name'] ?? 'Last name') ?>"
                  data-label-business="<?= hs_h($t['register_contact_last_name'] ?? 'Contact last name') ?>">
                  <?= hs_h($accountType === 'business' ? ($t['register_contact_last_name'] ?? 'Contact last name') : ($t['register_last_name'] ?? 'Last name')) ?> *
                </label>
                <input type="text" id="last_name" name="last_name" required value="<?= hs_h($post['last_name'] ?? '') ?>">
              </div>
            </div>
            <div class="hs-field">
              <label for="email"><?= hs_h($t['register_email'] ?? 'Email') ?> *</label>
              <input type="email" id="email" name="email" required autocomplete="email" value="<?= hs_h($post['email'] ?? '') ?>">
            </div>
            <div class="hs-field">
              <label for="phone"><?= hs_h($t['register_phone'] ?? 'Phone') ?> *</label>
              <input type="tel" id="phone" name="phone" required autocomplete="tel" placeholder="+47 000 00 000" value="<?= hs_h($post['phone'] ?? '') ?>">
            </div>
        </section>

        <!-- Step 4: Login -->
        <section class="hs-reg-panel" data-reg-step="4" hidden>
          <h2 class="hs-reg-panel-title"><i class="fa-solid fa-key"></i> <?= hs_h($t['register_step_access'] ?? 'Login') ?></h2>
          <p class="hs-reg-panel-hint"><?= hs_h($t['register_step_access_hint'] ?? '') ?></p>
          <div class="hs-field">
            <label for="username"><?= hs_h($t['register_username'] ?? '') ?> *</label>
            <input type="text" id="username" name="username" required pattern="[a-z0-9][a-z0-9_-]{2,31}" autocomplete="username" value="<?= hs_h($post['username'] ?? $demoUsername) ?>">
            <span class="hs-field-hint"><?= hs_h($t['register_username_hint'] ?? '') ?></span>
          </div>
          <div class="hs-field">
            <label for="password"><?= hs_h($isDemoPrefill ? ($t['register_password_demo'] ?? $t['register_password'] ?? '') : ($t['register_password'] ?? '')) ?> *</label>
            <input type="password" id="password" name="password" required minlength="<?= (int) $demoMinPass ?>" autocomplete="new-password"<?= $demoPassword !== '' ? ' value="' . hs_h($demoPassword) . '"' : '' ?>>
            <span class="hs-field-hint"><?= hs_h($isDemoPrefill ? ($t['register_password_demo_hint'] ?? $t['register_password_hint'] ?? '') : ($t['register_password_hint'] ?? '')) ?></span>
          </div>
        </section>

        <!-- Step 5: Address & domain -->
        <section class="hs-reg-panel" data-reg-step="5" hidden>
          <h2 class="hs-reg-panel-title"><i class="fa-solid fa-location-dot"></i> <?= hs_h($t['register_step_address'] ?? 'Address & domain') ?></h2>
          <p class="hs-reg-panel-hint"><?= hs_h($t['register_step_address_hint'] ?? '') ?></p>
          <h3 class="hs-reg-subtitle"><?= hs_h($t['register_section_billing'] ?? 'Billing address') ?></h3>
          <div class="hs-field">
            <label for="address"><?= hs_h($t['register_address'] ?? 'Street address') ?> *</label>
            <input type="text" id="address" name="address" required autocomplete="street-address" value="<?= hs_h($post['address'] ?? '') ?>">
          </div>
          <div class="hp-grid-2">
            <div class="hs-field">
              <label for="postal"><?= hs_h($t['register_postal'] ?? 'Postal code') ?> *</label>
              <input type="text" id="postal" name="postal" required autocomplete="postal-code" value="<?= hs_h($post['postal'] ?? '') ?>">
            </div>
            <div class="hs-field">
              <label for="city"><?= hs_h($t['register_city'] ?? 'City') ?> *</label>
              <input type="text" id="city" name="city" required autocomplete="address-level2" value="<?= hs_h($post['city'] ?? '') ?>">
            </div>
          </div>
          <div class="hs-field">
            <label for="country"><?= hs_h($t['register_country'] ?? 'Country') ?> *</label>
            <select id="country" name="country" required autocomplete="country" class="hs-country-select">
              <?= hs_render_country_options($lang, $selCountry, $t) ?>
            </select>
          </div>
          <h3 class="hs-reg-subtitle"><i class="fa-solid fa-globe"></i> <?= hs_h($t['register_section_domain'] ?? 'Domain') ?></h3>
          <div class="hs-field">
            <label for="domain_wish"><?= hs_h($t['register_domain_wish'] ?? 'Desired domain') ?></label>
            <input type="text" id="domain_wish" name="domain_wish" placeholder="mysite.no" value="<?= hs_h($post['domain_wish'] ?? $pendingDomain) ?>">
            <span class="hs-field-hint"><?= hs_h($t['register_domain_hint'] ?? '') ?></span>
          </div>
        </section>

        <!-- Step 6: Confirm -->
        <section class="hs-reg-panel" data-reg-step="6" hidden>
          <h2 class="hs-reg-panel-title"><i class="fa-solid fa-shield-halved"></i> <?= hs_h($t['register_step_confirm'] ?? 'Confirm') ?></h2>
          <p class="hs-reg-panel-hint"><?= hs_h($t['register_step_confirm_hint'] ?? '') ?></p>
          <div class="hs-reg-summary" data-reg-summary aria-live="polite"></div>
          <div class="hs-consent-banner">
            <p><?= hs_h($t['register_consent_text'] ?? '') ?></p>
            <label class="hs-consent-item"><input type="checkbox" name="consent_terms" value="1" required <?= !empty($post['consent_terms']) ? 'checked' : '' ?>> <?= hs_h($t['register_consent_terms'] ?? '') ?></label>
            <label class="hs-consent-item"><input type="checkbox" name="consent_privacy" value="1" required <?= !empty($post['consent_privacy']) ? 'checked' : '' ?>> <?= hs_h($t['register_consent_privacy'] ?? '') ?></label>
            <label class="hs-consent-item"><input type="checkbox" name="consent_marketing" value="1" <?= !empty($post['consent_marketing']) ? 'checked' : '' ?>> <?= hs_h($t['register_consent_marketing'] ?? '') ?></label>
          </div>
        </section>

        <div class="hs-reg-nav">
          <button type="button" class="hs-btn hs-btn-ghost" data-reg-back hidden>
            <i class="fa-solid fa-arrow-left"></i> <?= hs_h($t['register_btn_back'] ?? 'Back') ?>
          </button>
          <button type="button" class="hs-btn hs-btn-primary" data-reg-next>
            <?= hs_h($t['register_btn_next'] ?? 'Next') ?> <i class="fa-solid fa-arrow-right"></i>
          </button>
          <button type="submit" class="hs-btn hs-btn-primary hs-register-submit" data-reg-submit hidden>
            <i class="fa-solid fa-check"></i> <?= hs_h($t['register_consent_btn'] ?? 'I agree — create account') ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
$page_title = $t['register_title'] ?? '';
require __DIR__ . '/includes/layout-public.php';