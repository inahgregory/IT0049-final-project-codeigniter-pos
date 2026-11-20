<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= esc($title ?? 'Dashboard') ?> | Counter POS</title><link rel="stylesheet" href="<?= base_url('css/app.css') ?>"></head>
<body><a class="skip" href="#main">Skip to content</a>
<header class="header"><div class="bar"><a class="brand" href="<?= site_url('/') ?>"><span class="mark">C</span> Counter<span class="muted">POS</span></a><nav aria-label="Main navigation">
<?php if (session()->get('staff_id')): ?>
<?php foreach (['' => 'Dashboard', 'products' => 'Products', 'customers' => 'Customers', 'staff' => 'Staff', 'sales/create' => 'Record Sale', 'sales' => 'Sales History'] as $path => $label): ?><a class="nav-link" href="<?= site_url($path) ?>"><?= esc($label) ?></a><?php endforeach ?>
<form class="logout-form" action="<?= site_url('logout') ?>" method="POST"><?= csrf_field() ?><button class="nav-link logout-button" type="submit">Log out</button></form>
<?php else: ?><a class="nav-link" href="<?= site_url('about') ?>">About</a><a class="nav-link" href="<?= site_url('login') ?>">Log in</a><?php endif ?>
</nav></div></header>
<main id="main" class="container">
<?php if ($message = session()->getFlashdata('success')): ?><div class="success-message" role="status"><?= esc($message) ?></div><?php endif ?>
<?php if ($message = session()->getFlashdata('error')): ?><div class="validation-summary" role="alert"><?= esc($message) ?></div><?php endif ?>
<?php if ($errors = session()->getFlashdata('errors')): ?><div class="validation-summary" role="alert"><h2>Please check your details</h2><ul><?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<?= $this->renderSection('content') ?></main>
<footer class="footer"><div class="bar"><span>Counter POS &middot; CodeIgniter Midterm</span><span>Inah Angela Gregorio &middot; BSITCST - AC32</span></div></footer></body></html>
