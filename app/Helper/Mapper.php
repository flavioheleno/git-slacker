<?php
declare(strict_types = 1);

namespace App\Helper;

class Mapper {
  private $data = [];

  // public function __construct(string $file) {
  // }

  public function githubOrSlackQuote(string $username, string $profileUrl) : string {
    switch ($username) {
      case 'cmmp':
        return '<@U4ES7V291|cassio>';
      case 'flavioheleno':
        return '<@U4E75667J|flavioheleno>';
    }

    return sprintf('<%s|%s>', $profileUrl, $username);
  }

  public function githubToSlackQuote(string $username) : string {
    switch ($username) {
      case 'cmmp':
        return '<@U4ES7V291|cassio>';
      case 'flavioheleno':
        return '<@U4E75667J|flavioheleno>';
    }
  }

  public function githubToSlack(string $username) : string {
    switch ($username) {
      // case 'cmmp':
      //   return 'U4ES7V291';
      case 'flavioheleno':
        return 'U4E75667J';
    }

    return 'U4E75667J';
  }
}
