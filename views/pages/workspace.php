<?php
// views/pages/workspace.php

use CyberKavach\Nexus\Middleware\AuthMiddleware;
use CyberKavach\Nexus\Helpers\SecurityHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Protect route and pull active user profile
AuthMiddleware::handle();

use CyberKavach\Nexus\Config\Database;

$db = Database::getConnection();

$stmt = $db->prepare("
    SELECT *
    FROM event_workspaces
    ORDER BY created_at DESC
");

$stmt->execute();

$workspaces = $stmt->fetchAll();

$pageTitle = 'Smart Workspace - CyberKavach Nexus';
require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>

<div class="app-container">
    <?php require_once dirname(__DIR__, 2) . '/views/components/sidebar.php'; ?>

    <main class="main-content">
        <?php require_once dirname(__DIR__, 2) . '/views/components/navbar.php'; ?>

        <h1 class="page-title">
    Event Workspaces
</h1>

<div class="workspace-grid">

<?php foreach($workspaces as $workspace): ?>

<?php
$name = strtolower($workspace['name']);

$icon = "blocks";
$gradient = "linear-gradient(135deg,#6366f1,#3b82f6)";

if(str_contains($name,"security")){
    $icon="shield";
    $gradient="linear-gradient(135deg,#06b6d4,#3b82f6)";
}
elseif(str_contains($name,"design")){
    $icon="palette";
    $gradient="linear-gradient(135deg,#ec4899,#8b5cf6)";
}
elseif(str_contains($name,"development")){
    $icon="code-2";
    $gradient="linear-gradient(135deg,#6366f1,#8b5cf6)";
}
elseif(str_contains($name,"documentation")){
    $icon="file-text";
    $gradient="linear-gradient(135deg,#10b981,#06b6d4)";
}
?>

<div class="workspace-card">

    <div class="workspace-icon"
         style="background:<?= $gradient ?>">

        <i data-lucide="<?= $icon ?>"></i>

    </div>

    <h3>
        <?= SecurityHelper::escape($workspace['name']) ?>
    </h3>

    <a href="/cyber2/views/pages/workspace_details.php?id=<?= $workspace['id'] ?>"
class="workspace-open-btn">

Open

<i data-lucide="arrow-right"></i>

</a>

</div>

<?php endforeach; ?>

</div>
    </main>
</div>

<style>

.workspace-grid{
display:grid;
grid-template-columns:repeat(auto-fill,minmax(280px,320px));
gap:28px;
margin-top:2rem;
justify-content:start;
align-items:start;
}

.workspace-card{
height:280px;
background:#0f172a;
border:1px solid rgba(255,255,255,.08);
border-radius:28px;
padding:32px;

display:flex;
flex-direction:column;
align-items:center;
justify-content:center;

transition:.3s;
position:relative;
overflow:hidden;
}

.workspace-card:hover{
transform:translateY(-5px);
border-color:rgba(99,102,241,.4);
}

.workspace-icon{
width:85px;
height:85px;
border-radius:24px;

display:flex;
justify-content:center;
align-items:center;

margin-bottom:28px;
}

.workspace-icon svg{
width:40px;
height:40px;
color:white;
}

.workspace-card h3{
font-size:1.5rem;
font-weight:700;
margin-bottom:25px;
text-align:center;
}

.workspace-card p{
color:#94a3b8;
line-height:1.7;
margin-bottom:2rem;
}

.workspace-open-btn{
width:150px;
height:52px;

display:flex;
justify-content:center;
align-items:center;
gap:12px;

border-radius:16px;

background:white;
color:#111827;

font-size:1rem;
font-weight:700;

text-decoration:none;

transition:.3s;
}

.workspace-open-btn:hover{
background:#6366f1;
color:white;
transform:scale(1.05);
}

.workspace-open-btn svg{
width:18px;
height:18px;
transition:.3s;
}

.workspace-open-btn:hover svg{
transform:translateX(4px);
}

</style>

<?php
require_once dirname(__DIR__, 2) . '/views/layouts/footer.php';
?>
