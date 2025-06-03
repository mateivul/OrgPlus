<?php

spl_autoload_register(function ($className) {
    // Defineste directorul radacina al aplicatiei (unde este App\)
    $baseDir = __DIR__ . '/'; // Asta ar trebui să fie 'src/'

    // Transformă numele clasei din namespace-uri in cale de fisier
    // Ex: App\Repository\UserRepository -> App/Repository/UserRepository.php
    // Inlocuieste "App\" cu "src/" (sau directorul tau de baza)
    $prefix = 'App\\'; // Prefixul namespace-ului nostru
    $prefixLength = strlen($prefix);

    // Daca numele clasei NU incepe cu prefixul nostru, lasa autoloader-ul PHP sa isi faca treaba
    if (strncmp($prefix, $className, $prefixLength) !== 0) {
        return;
    }

    // Extrage partea relativa a numelui clasei (ex: Repository\UserRepository)
    $relativeClass = substr($className, $prefixLength);

    // Formeaza calea completa a fisierului
    // Inlocuieste separatorii de namespace cu separatori de director
    // Adauga extensia .php
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // Daca fisierul exista, include-l
    if (file_exists($file)) {
        require_once $file;
    }
});
