<?php
use Respect\Validation\Validator as DataValidator;
DataValidator::with('CustomValidations', true);

/**
 * @api {post} /ticket/check Check ticket
 * @apiVersion 4.8.0
 *
 * @apiName Check ticket
 *
 * @apiGroup Ticket
 *
 * @apiDescription This path logs you in to see a ticket, but only when there is no users.
 *
 * @apiPermission any
 *
 * @apiParam {Number} ticketNumber The number of a ticket.
 * @apiParam {String} email Email of the person who created the ticket.
 * @apiParam {String} captcha Encrypted value generated by google captcha client.
 *
 * @apiUse INVALID_TICKET
 * @apiUse INVALID_EMAIL
 * @apiUse INVALID_CAPTCHA 
 * @apiUse NO_PERMISSION
 *
 * @apiSuccess {Object} data Data for the ticket session
 * @apiSuccess {String} data.token Token of the ticket session
 * @apiSuccess {Number} data.ticketNumber Number of the ticket 
 *
 */

class CheckTicketController extends Controller {
    const PATH = '/check';
    const METHOD = 'POST';

    public function validations() {
        return [
            'permission' => 'any',
            'requestData' => [
                'ticketNumber' => [
                    'validation' => DataValidator::validTicketNumber(),
                    'error' => ERRORS::INVALID_TICKET
                ],
                'email' => [
                    'validation' => DataValidator::email(),
                    'error' => ERRORS::INVALID_EMAIL
                ],
                'captcha' => [
                    'validation' => DataValidator::captcha(),
                    'error' => ERRORS::INVALID_CAPTCHA
                ]
            ]
        ];
    }

    public function handler() {
        if (Controller::isLoginMandatory()) {
            throw new RequestException(ERRORS::NO_PERMISSION);
        }

        $email = Controller::request('email');
        $ticketNumber = Controller::request('ticketNumber');
        $ticket = Ticket::getByTicketNumber($ticketNumber);

        if($ticket->authorEmail === $email) {
            $session = Session::getInstance();
            $user = User::getUser($email, 'email');

            $session->createSession($user->id, false, $ticketNumber);
            Response::respondSuccess([
                'token' => $session->getToken(),
                'userId' => $session->getUserId(),
                'ticketNumber' => $session->getTicketNumber()
            ]);
        } else {
            throw new RequestException(ERRORS::NO_PERMISSION);
        }
    }
}