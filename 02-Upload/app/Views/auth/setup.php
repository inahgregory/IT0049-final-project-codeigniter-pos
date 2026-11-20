<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="auth-shell"><div class="page-heading compact"><p class="eyebrow">LET'S OPEN YOUR STORE</p><h1>Create your first staff account.</h1><p class="lead">Choose your own login. This setup page closes once the first account exists.</p></div>
<form class="account-form" action="<?= site_url('setup') ?>" method="POST"><?= csrf_field() ?><div class="auth-fields">
<div class="field"><label for="username">Username</label><input id="username" name="username" value="<?= esc(old_field('username'), 'attr') ?>" required minlength="3" maxlength="50" autocomplete="username" autofocus><p class="field-hint">3–50 lowercase letters, numbers, dots, underscores, or hyphens.</p></div>
<div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" value="<?= esc(old_field('full_name'), 'attr') ?>" required maxlength="100" autocomplete="name"></div>
<div class="field"><label for="password">Password</label><input id="password" type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"><p class="field-hint">Use at least 8 characters. Keep your password private.</p></div>
<div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
<button class="button" type="submit">Create staff account</button></div></form></div>
<?= $this->endSection() ?>
