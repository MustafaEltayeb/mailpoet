<?php

namespace MailPoet\Test\Acceptance;

class SubscribersListingCest {

  function subscribersListing(\AcceptanceTester $I) {
    $I->wantTo('Open subscribers listings page');

    $I->login();
    $I->amOnMailpoetPage('Subscribers');
    $I->waitForElement('#search_input');
    $I->wait(2);
    $I->fillField('#search_input', 'wp@example.com');
    $I->click('Search');
    $I->waitForText('wp@example.com', 10);
  }

}
