<?php

namespace Templates\Mdb\Forms;



/**
 * MDB Plugin SignIn form class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class SignInForm extends \Phoundation\Web\Http\Html\Forms\SignInForm
{
    /**
     * Render the HTML for this Sign-in form
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->render = '<!-- Email input -->
                          <div class="form-outline mb-4">
                            <input type="email" id="email" name="email" class="form-control" />
                            <label class="form-label" for="email">' . tr('Email address') . '</label>
                          </div>
                        
                          <!-- Password input -->
                          <div class="form-outline mb-4">
                            <input type="password" id="password" name="password" class="form-control" />
                            <label class="form-label" for="password">' . tr('Password') . '</label>
                          </div>
                        
                          <!-- 2 column grid layout for inline styling -->
                          <div class="row mb-4">
                            <div class="col d-flex justify-content-center">
                              <!-- Checkbox -->
                              <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="remember_me" name="remember_me" checked />
                                <label class="form-check-label" for="remember_me"> ' . tr('Remember me') . ' </label>
                              </div>
                            </div>
                        
                            <div class="col">
                              <!-- Simple link -->
                              <a href="' . $this->forgot_password_url . '">' . tr('Forgot password?') . '</a>
                            </div>
                          </div>
                        
                          <!-- Submit button -->
                          <button type="submit" class="btn btn-primary btn-block mb-4">' . tr('Sign in') . '</button>
                        
                          <!-- Register buttons -->
                          <div class="text-center">
                            <p>' . tr('Not a member?') . ' <a href="' . $this->register_url . '">' . tr('Register') . '</a></p>
                            <p>' . tr('or sign up with:') . '</p>
                            <button type="button" class="btn btn-primary btn-floating mx-1">
                              <i class="fab fa-facebook-f"></i>
                            </button>
                        
                            <button type="button" class="btn btn-primary btn-floating mx-1">
                              <i class="fab fa-google"></i>
                            </button>
                        
                            <button type="button" class="btn btn-primary btn-floating mx-1">
                              <i class="fab fa-twitter"></i>
                            </button>
                        
                            <button type="button" class="btn btn-primary btn-floating mx-1">
                              <i class="fab fa-github"></i>
                            </button>
                          </div>';

       return parent::render();
    }
}