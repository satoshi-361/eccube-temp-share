<?php

namespace Plugin\EccubePaymentLite4\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait
{
    /**
     * @ORM\Column(name="gmo_epsilon_credit_card_expiration_date", type="datetimetz", nullable=true)
     */
    private $gmo_epsilon_credit_card_expiration_date;

    /**
     * @ORM\Column(name="card_change_request_mail_send_date", type="datetimetz", nullable=true)
     */
    private $card_change_request_mail_send_date;

    public function getGmoEpsilonCreditCardExpirationDate()
    {
        return $this->gmo_epsilon_credit_card_expiration_date;
    }

    public function setGmoEpsilonCreditCardExpirationDate(DateTime $gmo_epsilon_credit_card_expiration_date)
    {
        $this->gmo_epsilon_credit_card_expiration_date = $gmo_epsilon_credit_card_expiration_date;
    }

    public function getCardChangeRequestMailSendDate()
    {
        return $this->card_change_request_mail_send_date;
    }

    public function setCardChangeRequestMailSendDate($card_change_request_mail_send_date): self
    {
        $this->card_change_request_mail_send_date = $card_change_request_mail_send_date;

        return $this;
    }
}
