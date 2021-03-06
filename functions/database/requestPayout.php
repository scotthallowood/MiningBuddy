<?php


/*
 * MiningBuddy (http://miningbuddy.net)
 * $Header: /usr/home/mining/cvs/mining/functions/database/requestPayout.php,v 1.4 2008/01/12 22:18:58 mining Exp $
 *
 * Copyright (c) 2005-2008 Christian Reiss.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms,
 * with or without modification, are permitted provided
 * that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 *
 *  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 *  "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 *  LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 *  FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 *  OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 *  SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 *  TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA,
 *  OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 *  OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 *  NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 *  SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
 
 function requestPayout () {
 	
 	// Globals
 	global $MySelf;
 	global $DB;
 	global $TIMEMARK;
	
	// How much overdraft are we allowed?
	$overdraft = 100 * 1000000; // 100m
	$overdraftlimit = false;
	
	// How much isk we got?
	$MyCredits = getCredits($MySelf->getID());

	// Is this a number?
	if (!is_numeric($_POST[amount])) {
 		makeNotice("The frog looks at you and your cheque with the amount of \"".$_POST[amount]."\". The frog is unsure how much ISK that is and instead decides to lick your face in a friendly manner, then it closes the teller and goes for lunch.", "warning", "Huh?");
 	}
 	
 	// We are requesting a POSITIVE amount, right?
 	if (!numericCheckBool($_POST[amount],0)) {
 		makeNotice("You can only request positive amounts of ISK. If you want money, go work for it.",
 		"notice", "This aint no charity", "index.php?action=manageWallet", "But i got women and children to feed...");
 	}
 	
 	// So, can we afford it?
 	if ($overdraft <= 0 && !numericCheckBool($_POST[amount], 1, $MyCredits)){
 		makeNotice("You can only request a payment up to " . number_format($MyCredits). 
                   " ISK. You requested " . number_format($_POST[amount]).
                   " ISK. Thats " . number_format(($_POST[amount] - $MyCredits),2) .
                   " ISK more than you can afford.", "warning", "Too big of a payout.", 
                   "index.php?action=manageWallet", "[Cancel]");
 	}
	
	// Allow an overdraft, but not too much
	if ($overdraft > 0 && $overdraftlimit && !numericCheckBool($_POST[amount], 1, $MyCredits + $overdraft)){
 		makeNotice("You can only request a payment up to " . number_format($MyCredits + $overdraft). 
                   " ISK. You requested " . number_format($_POST[amount]).
                   " ISK. Thats " . number_format(($_POST[amount] - ($MyCredits  + $overdraft)),2) .
                   " ISK more than you are allowed.", "warning", "Too big of a payout.", 
                   "index.php?action=manageWallet", "[Cancel]");
 	}
	
	// We sure?
	confirm("Please confirm your payout request of " . number_format($_POST[amount],2) . " ISK.");
	
	// Ok, do it.
	$DB->query("INSERT INTO payoutRequests (time, applicant, amount) VALUES (?,?,?)",
	           array($TIMEMARK, $MySelf->getID(), $_POST[amount]));
	
	if ($DB->affectedRows() == 1){
		mailUser("We are notifying you that ".$MySelf->getUsername()." has requested a payout of ".number_format($_POST[amount],2)." ISK","WHB Payout Requested","isAccountant");
		makeNotice("You request has been logged. An accountant will soon honor your request.", "notice", "Request logged", "index.php?action=manageWallet", "[OK]");
	} else {
		makeNotice("Internal Error! Unable to record your request into the database! Inform the admin!", "error", "Internal Error!", "index.php?action=manageWallet", "[cancel]");
	}
 }