<?php
/*
 * This file is part of the Goteo Package.
 *
 * (c) Platoniq y Fundación Goteo <fundacion@goteo.org>
 *
 * For the full copyright and license information, please view the README.md
 * and LICENSE files that was distributed with this source code.
 */

namespace Goteo\Util\MatcherProcessor;

use Goteo\Util\MatcherProcessor\AbstractMatcherProcessor;
use Goteo\Util\MatcherProcessor\MatcherProcessorException;
use Goteo\Model\Matcher;
use Goteo\Model\Invest;
use Goteo\Payment\Method\PoolPaymentMethod;

/**
 * This Processor duplicates invests with some (customizable) limits
 */
class DuplicateInvestMatcherProcessor extends AbstractMatcherProcessor {
    protected $default_vars = [
        'max_amount_per_project' => 500,
        'max_amount_per_invest' => 100,
        'max_invests_per_user' => 1
    ];

    static public function getVarLabels() {
        return [
            'max_amount_per_project' => 'Maximum total amount to be multiplied per project',
            'max_amount_per_invest' => 'Maximum multiply per invest',
            'max_invests_per_user' => 'Maximum number of invests per user in the project'
        ];
    }

    /**
     */
    // public function getAmount() {

    // }

    /**
     * Checks if this invests has to be multiplied and
     * returns the amount to be added
     */
    public function getAmount() {
        $invest = $this->getInvest();
        $project = $this->getProject();
        $matcher = $this->getMatcher();
        $vars = $this->getVars();
        $amount = $invest->amount;

        if($amount > $vars['max_amount_per_invest']) {
            $amount = $vars['max_amount_per_invest'];
        }

        $invested = Invest::getList(['methods' => 'pool', 'projects' => $project,'users' => $matcher->getUsers()], null, 0, 0, 'money');
        // echo "[$invested]";
        if($invested + $amount > $vars['max_amount_per_project']) {
            $amount = max(0, $vars['max_amount_per_project'] - $invested);
        }
        $count = Invest::getList(['projects' => $project, 'users' => $invest->user], null, 0, 0, 'user');

        if($count >= $vars['max_invests_per_user']) {
            $amount = 0;
        }

        if($matcher->getTotalAmount() < $amount) {
            $amount = $matcher->getTotalAmount();
        }

        return $amount;

    }

    public function getInvests() {
        $matcher = $this->getMatcher();
        $invest = $this->getInvest();
        $project = $this->getProject();
        // $method = $this->getMethod();
        $vars = $this->getVars();

        // Ensure is enough amount
        if($amount = $this->getAmount()) {

            // Check if there's enough total to extract from user's pool
            if($matcher->getTotalAmount() < $amount) {
                throw new MatcherProcessorException("Not enough amount to match");
            }

            $list = [];
            foreach($this->getUserAmounts($amount) as $user_id => $user_amount) {
                $list[] = new Invest([
                    'amount'    => $user_amount,
                    'user'      => $user_id,
                    'currency'  => $invest->currency,
                    'currency_rate' => $invest->currency_rate,
                    'project'   => $project->id,
                    'method'    => PoolPaymentMethod::getId(),
                    'status'    => $invest->status,
                    'invested'  => date('Y-m-d'),
                    'anonymous' => false,
                    'resign'    => false,
                    'campaign'  => true,
                    'drops'     => $invest->id,
                    // 'matcher'      => $matcher->id
                ]);
            }
            return $list;

        }
        throw new MatcherProcessorException("Amount to match is zero due internal rules");
    }

}
