<?php

/* =============================================================================
    USAGES (one liners !!! no need to instaciate or anything) :
        - Set one value :
            SessionManager::set('user_id', 123);
        - Set Multiple Values :
            SessionManager::set([
                'username' => 'johndoe',
                'role' => 'admin',
                'logged_in' => true
            ]);
        - Read a value :
            echo SessionManager::get('username');
        - Delete a value :
            SessionManager::delete('username');
        - Delete multiple Values :
            SessionManager::delete(['role', 'logged_in']);
        - Delete ALL session
            SessionManager::destroy();
============================================================================= */

class SessionManager
{
    // Démarre la session si elle n'est pas déjà active
    private static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Définir une ou plusieurs valeurs dans la session
    public static function set($key, $value = null)
    {
        self::start();

        // Si un tableau est passé, on boucle dessus
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $_SESSION[$k] = $v;
            }
        } else {
            $_SESSION[$key] = $value;
        }

        // Ferme la session immédiatement après écriture
        session_write_close();
    }

    // Récupérer une valeur de la session
    public static function get($key)
    {
        self::start();
        return $_SESSION[$key] ?? null;
        session_write_close();
    }

    // Supprimer une ou plusieurs clés dans la session
    public static function delete($key)
    {
        self::start();
        if (is_array($key)) {
            foreach ($key as $k) {
                unset($_SESSION[$k]);
            }
        } else {
            unset($_SESSION[$key]);
        }
        session_write_close();
    }

    // Détruire complètement la session (en cas de deco ?)
    public static function destroy()
    {
        self::start();
        session_unset();
        session_destroy();
        // session_write_close();
    }

}
