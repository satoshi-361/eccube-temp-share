<?php
namespace Plugin\JsysAsi\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\ToggleSwitchType;
use Plugin\JsysAsi\Entity\Config;
use Plugin\JsysAsi\Service\JsysAsiCryptService;

/**
 * プラグイン設定FormType
 * @author manabe
 *
 */
class ConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var JsysAsiCryptService
     */
    protected $jsysAsiCryptService;

    /**
     * @var ValidatorInterface
     */
    private $validator;


    /**
     * @param ValidatorInterface $validator
     * @param EccubeConfig $eccubeConfig
     * @param JsysAsiCryptService $jsysAsiCryptService
     */
    public function __construct(
        ValidatorInterface $validator,
        EccubeConfig $eccubeConfig,
        JsysAsiCryptService $jsysAsiCryptService
    ) {
        $this->validator           = $validator;
        $this->eccubeConfig        = $eccubeConfig;
        $this->jsysAsiCryptService = $jsysAsiCryptService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('option_tfa', ToggleSwitchType::class)
            ->add('option_tfa_master_key', TextType::class, [
                'required' => false,
                'attr'     => ['readonly' => true],
            ])
            ->add('option_login_success_mail', ToggleSwitchType::class)
            ->add('option_login_failure_mail', ToggleSwitchType::class)
            ->add('option_ip_address_lock', ToggleSwitchType::class)
            ->add('option_ip_address_lock_count', IntegerType::class, [
                'required' => false,
            ]);

        // フォームの更新、初期値の設定、値の検証とデータの更新を追加
        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData'])
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }

    /**
     * フォームの更新を行います。
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var \Plugin\JsysAsi\Entity\Config $Config */
        $Config = $event->getData();
        $form   = $event->getForm();

        $masterKey = $Config->getOptionTfaMasterKey();
        if ($masterKey) {
            // マスターキーを復号化し、フォームへ設定
            $masterKey = $this->jsysAsiCryptService->decrypt(
                $masterKey,
                $Config->getOptionTfaMasterKeyPassword(),
                $Config->getOptionTfaMasterKeySalt()
            );
            $form['option_tfa_master_key']->setData($masterKey);
        }
    }

    /**
     * 送信されたリクエストへ条件付きで初期値を設定します。
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $this->setDefaultValue($data, [
            'option_tfa',
            'option_login_success_mail',
            'option_login_failure_mail',
            'option_ip_address_lock',
        ]);

        // 2要素認証が無効の場合、2要素認証マスターキーへNULLを設定
        if (!$data['option_tfa']) {
            $data['option_tfa_master_key'] = null;
        }

        // IPアドレスロックが無効の場合、ロックまでの回数へNULLを設定
        if (!$data['option_ip_address_lock']) {
            $data['option_ip_address_lock_count'] = null;
        }

        $event->setData($data);
    }

    /**
     * 値の検証とデータの更新を追加します。
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var \Plugin\JsysAsi\Entity\Config $data */
        $data = $form->getData();

        // 追加の検証
        $this->addValidate($form);

        // 2要素認証
        if (!$data->getOptionTfa()) {
            // 無効の場合はパスワード・ソルトへNULLを設定
            $data
                ->setOptionTfaMasterKeyPassword(null)
                ->setOptionTfaMasterKeySalt(null);

        } elseif ($form['option_tfa_master_key']->isValid()) {
            // 有効かつ検証成功であればマスターキーを暗号化
            $masterKey = $data->getOptionTfaMasterKey();
            $password  = $this->jsysAsiCryptService->createPassword();
            $salt      = $this->jsysAsiCryptService->createSalt();
            $masterKey = $this->jsysAsiCryptService->encrypt(
                $masterKey,
                $password,
                $salt
            );
            if (false === $masterKey) {
                $form['option_tfa_master_key']->addError(new FormError(trans(
                    'jsys_asi.encrypt.failure'
                )));
                return;
            }

            // マスターキー・パスワード・ソルトを設定
            $data
                ->setOptionTfaMasterKey($masterKey)
                ->setOptionTfaMasterKeyPassword($password)
                ->setOptionTfaMasterKeySalt($salt);
        }
    }

    /**
     * 存在しないまたはNULLの項目へ初期値を設定します。
     * @param array $data
     * @param array $columns
     * @param mixed $default
     */
    private function setDefaultValue(&$data, array $columns, $default = false)
    {
        foreach ($columns as $column) {
            if (!isset($data[$column]) || is_null($data[$column])) {
                $data[$column] = $default;
            }
        }
    }

    /**
     * 追加の検証を行います。
     * @param FormInterface $form
     */
    private function addValidate($form)
    {
        $config      = $this->eccubeConfig;
        $errors      = [];
        $createError = function ($column, $constraints) use (&$errors, $form) {
            if (!$constraints) {
                return;
            }
            $value      = $form[$column]->getData();
            $violations = $this->validator->validate($value, $constraints);
            foreach ($violations as $violation) {
                $errors[$column][] = $violation->getMessage();
            }
        };

        // 2要素認証が有効の場合、2要素認証マスターキーへ検証を追加
        if ($form['option_tfa']->getData()) {
            $createError('option_tfa_master_key', [
                new Assert\NotBlank(),
                new Assert\Length(['max' => $config['eccube_stext_len']]),
            ]);
        }

        // IPアドレスロックが有効の場合、ロックまでの回数へ検証を追加
        if ($form['option_ip_address_lock']->getData()) {
            $createError('option_ip_address_lock_count', [
                new Assert\NotBlank(),
                new Assert\Length(['max' => $config['eccube_int_len']]),
                new Assert\Range(['min' => 1]),
            ]);
        }

        // エラーをフォームへ追加
        foreach ($errors as $column => $error) {
            foreach ($error as $message) {
                $form[$column]->addError(new FormError($message));
            }
        }
    }

}
