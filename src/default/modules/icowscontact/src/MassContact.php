<?php

namespace Drupal\icowscontact;

class MassContact {

  public function icowscontact_notify_users($subject, $message) {
    notify_users($subject, $message);
  }

}