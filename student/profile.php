<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../admin/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireStudent();

renderHeader('Student Profile', 'student');
?>
<h1 class="dash-title">Profile</h1>
<p class="dash-subtitle">Update your student details for faster checkout and order coordination.</p>

<div class="dash-panel">
    <form method="post" action="#" class="login-form" onsubmit="return false;">
        <label for="student_name">Full Name</label>
        <input id="student_name" type="text" value="" placeholder="Enter full name">

        <label for="student_no">Student Number</label>
        <input id="student_no" type="text" value="" placeholder="Enter student number">

        <label for="student_email">Email</label>
        <input id="student_email" type="email" value="" placeholder="name@g.batstate-u.edu.ph">

        <button type="submit">Save Profile</button>
    </form>
    <p class="small" style="margin-top:0.7rem;">Profile persistence can be connected to a dedicated student account table when ready.</p>
</div>
<?php renderFooter('student');
