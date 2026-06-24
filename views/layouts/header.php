<?php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use CyberKavach\Nexus\Helpers\SecurityHelper;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= SecurityHelper::escape($pageTitle ?? 'CyberKavach Nexus') ?></title>

<link rel="stylesheet" href="<?= SecurityHelper::asset('assets/css/styles.css') ?>">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/lucide@0.321.0/dist/umd/lucide.min.js"></script>

<style>
#splash-screen{
position:fixed;
inset:0;
background:radial-gradient(circle at center,#07172e 0%,#020617 60%,#000814 100%);
display:flex;
justify-content:center;
align-items:center;
overflow:hidden;
z-index:999999;
transition:1s;
}

/* Grid */

.cyber-grid{
position:absolute;
width:200%;
height:200%;
background:
linear-gradient(rgba(0,153,255,.05) 1px,transparent 1px),
linear-gradient(90deg,rgba(0,153,255,.05) 1px,transparent 1px);
background-size:50px 50px;
animation:gridMove 12s linear infinite;
}

/* Cyber corners */

.corner{
position:absolute;
width:120px;
height:120px;
border-color:#1da1ff;
opacity:.7;
}

.tl{
top:30px;
left:30px;
border-top:3px solid;
border-left:3px solid;
}

.tr{
top:30px;
right:30px;
border-top:3px solid;
border-right:3px solid;
}

.bl{
bottom:30px;
left:30px;
border-bottom:3px solid;
border-left:3px solid;
}

.br{
bottom:30px;
right:30px;
border-bottom:3px solid;
border-right:3px solid;
}

/* particles */

.particles span{
position:absolute;
width:3px;
height:3px;
background:#3ea6ff;
border-radius:50%;
box-shadow:0 0 12px #3ea6ff;
animation:particle 8s infinite linear;
}

.particles span:nth-child(1){top:20%;left:10%;}
.particles span:nth-child(2){top:70%;left:20%;}
.particles span:nth-child(3){top:40%;left:80%;}
.particles span:nth-child(4){top:85%;left:70%;}
.particles span:nth-child(5){top:30%;left:60%;}
.particles span:nth-child(6){top:60%;left:90%;}
.particles span:nth-child(7){top:15%;left:45%;}
.particles span:nth-child(8){top:80%;left:40%;}

/* scan line */

.scan-line{
position:absolute;
top:-100%;
width:100%;
height:300px;
background:linear-gradient(
180deg,
transparent,
rgba(59,130,246,.06),
transparent
);
animation:scanMove 5s linear infinite;
}

/* center */

.logo-wrapper{
position:relative;
display:flex;
flex-direction:column;
align-items:center;
z-index:10;
}

/* shield */

/* FUTURISTIC SHIELD */

.shield{
width:140px;
height:140px;
display:flex;
justify-content:center;
align-items:center;
position:relative;
background:rgba(255,255,255,.03);
backdrop-filter:blur(15px);
clip-path:polygon(
50% 0%,
88% 18%,
88% 62%,
50% 100%,
12% 62%,
12% 18%
);

border:1px solid rgba(59,130,246,.4);

box-shadow:
0 0 25px rgba(59,130,246,.7),
0 0 60px rgba(59,130,246,.5),
inset 0 0 20px rgba(59,130,246,.3);

animation:pulseShield 2s infinite ease-in-out;
}

.shield::before{
content:'';
position:absolute;
inset:-10px;
clip-path:inherit;
border:1px solid rgba(59,130,246,.15);
animation:rotateBorder 8s linear infinite;
}

.shield::after{
content:'';
position:absolute;
inset:-22px;
clip-path:inherit;
border:1px solid rgba(59,130,246,.08);
animation:rotateBorderReverse 10s linear infinite;
}

.shield svg{
width:70px;
height:70px;
color:#47a8ff;
filter:
drop-shadow(0 0 15px #3b82f6)
drop-shadow(0 0 40px #3b82f6);
}



@keyframes rotateBorder{
100%{
transform:rotate(360deg);
}
}

@keyframes rotateBorderReverse{
100%{
transform:rotate(-360deg);
}
}

/* title */

.logo-title{
margin-top:40px;
text-align:center;
font-size:5rem;
font-weight:800;
letter-spacing:10px;
line-height:1.2;
}

.logo-title .cyber{
display:block;
color:white;
}

.logo-title .nexus{
display:block;
color:#4ea3ff;
text-shadow:
0 0 15px #4ea3ff,
0 0 40px #3ea6ff,
0 0 80px rgba(62,166,255,.8);
}

/* tagline */

.typewriter{
margin-top:25px;
color:#94a3b8;
font-size:.95rem;
font-weight:500;
letter-spacing:5px;

overflow:hidden;
white-space:nowrap;

width:0;

border-right:2px solid #3b82f6;

animation:
typing 3s steps(42,end) forwards,
blink .8s infinite;
}

.typewriter{
display:inline-block;
margin-top:25px;
color:#94a3b8;
font-size:.95rem;
letter-spacing:5px;
overflow:hidden;
white-space:nowrap;
width:0;
border-right:2px solid #3b82f6;

animation:
typing 3s steps(42,end) forwards,
blink .8s infinite;
}

@keyframes typing{
from{
width:0;
}
to{
width:630px;
}
}

@keyframes blink{
50%{
border-color:transparent;
}
}
</style>

</head>

<body>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);

if ($currentPage === 'login.php'):
?>

<div id="splash-screen"></div>

    <div class="cyber-grid"></div>

    <div class="logo-wrapper">

        <div class="shield">
            <i data-lucide="shield"></i>
        </div>

        <h1 class="logo-title">
            <span class="cyber">CYBERKAVACH</span>
            <span class="nexus">NEXUS</span>
        </h1>

        <div class="typewriter">
            SECURING TOMORROW THROUGH CYBER EXCELLENCE
        </div>

    </div>

</div>

<script>
window.addEventListener("load", () => {

    if(window.lucide){
        lucide.createIcons();
    }

    setTimeout(() => {

        const splash =
            document.getElementById("splash-screen");

        if(splash){

            splash.style.opacity="0";

            setTimeout(()=>{
                splash.remove();
            },1000);

        }

    },3500);

});
</script>

<?php endif; ?>

<div class="toast-container" id="toastContainer"></div>

