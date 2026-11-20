<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="auth-shell"><div class="page-heading compact"><p class="eyebrow">WELCOME BACK</p><h1>Your store starts here.</h1><p class="lead">Log in with your staff username to manage your store and record sales.</p></div>
<form class="account-form" action="<?= site_url('login') ?>" method="POST"><?= csrf_field() ?><div class="auth-fields">
<div class="field"><label for="username">Username</label><input id="username" name="username" value="<?= esc(old_field('username'), 'attr') ?>" required maxlength="50" autocomplete="username" autofocus></div>
<div class="field"><label for="password">Password</label><input id="password" type="password" name="password" required autocomplete="current-password"></div>
<button class="button" type="submit">Log in</button></div></form>
<?php if ($canSetup): ?><p class="auth-note">First time here? <a class="text-link" href="<?= site_url('setup') ?>">Create the first staff account &rarr;</a></p><?php endif ?>
</div>
<?= $this->endSection() ?>
