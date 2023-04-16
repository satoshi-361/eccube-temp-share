<?php
namespace Plugin\JsysAsi\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Eccube\Controller\AbstractController;
use Eccube\Repository\MemberRepository;
use Plugin\JsysAsi\Entity\JsysAsiTfaUser;
use Plugin\JsysAsi\Form\Type\Admin\JsysAsiTfaUserType;
use Plugin\JsysAsi\Repository\JsysAsiTfaUserRepository;
use Plugin\JsysAsi\Service\JsysAsiTfaService;
use Plugin\JsysAsi\Service\JsysAsiCryptService;

/**
 * 管理 > 2要素認証 > ユーザー登録 Controller
 * @author manabe
 *
 */
class JsysAsiTfaUserController extends AbstractController
{
    /**
     * @var MemberRepository
     */
    protected $memberRepository;

    /**
     * @var JsysAsiTfaUserRepository
     */
    protected $jsysAsiTfaUserRepository;

    /**
     * @var JsysAsiCryptService
     */
    protected $jsysAsiCryptService;

    /**
     * @var JsysAsiTfaService
     */
    protected $jsysAsiTfaService;


    /**
     * JsysAsiTfaUserController constructor.
     * @param MemberRepository $memberRepository
     * @param JsysAsiTfaUserRepository $jsysAsiTfaUserRepository
     * @param JsysAsiCryptService $jsysAsiCryptService
     * @param JsysAsiTfaService $jsysAsiTfaService
     */
    public function __construct(
        MemberRepository $memberRepository,
        JsysAsiTfaUserRepository $jsysAsiTfaUserRepository,
        JsysAsiCryptService $jsysAsiCryptService,
        JsysAsiTfaService $jsysAsiTfaService
    ) {
        $this->memberRepository         = $memberRepository;
        $this->jsysAsiTfaUserRepository = $jsysAsiTfaUserRepository;
        $this->jsysAsiCryptService      = $jsysAsiCryptService;
        $this->jsysAsiTfaService        = $jsysAsiTfaService;
    }

    /**
     * 2要素認証ユーザーの一覧表示を行います。
     * @param Request $request
     * @return array
     *
     * @Route(
     *   "/%eccube_admin_route%/jsys_asi_tfa_user/",
     *   name="admin_jsys_asi_tfa_user"
     * )
     * @Template("@JsysAsi/admin/tfa_user_index.twig")
     */
    public function index(Request $request)
    {
        // メンバー取得
        $Members  = $this->memberRepository->findBy([], ['sort_no' => 'DESC']);

        // メンバーIDをキーにした、2要素認証ユーザー情報(ID・QRコード)配列を作成
        $datas    = $this->jsysAsiTfaUserRepository->findBy(['enabled' => true]);
        $tfaUsers = [];
        /** @var JsysAsiTfaUser $data */
        foreach ($datas as $data) {
            $user     = new \stdClass();
            $user->id = $data->getId();
            $user->qr = $this->jsysAsiTfaService->getQRCode($data);
            $tfaUsers[$data->getMemberId()] = $user;
        }

        return [
            'Members'  => $Members,
            'TfaUsers' => $tfaUsers,
        ];
    }

    /**
     * 2要素認証ユーザーの登録画面を表示します。
     * @param Request $request
     * @param int $member_id
     * @throws NotFoundHttpException
     * @return array
     *
     * @Route(
     *   "/%eccube_admin_route%/jsys_asi_tfa_user/{member_id}/edit",
     *   requirements={"member_id" = "\d+"},
     *   name="admin_jsys_asi_tfa_user_edit"
     * )
     * @Template("@JsysAsi/admin/tfa_user_edit.twig")
     */
    public function edit(Request $request, int $member_id)
    {
        // 2要素認証を設定するメンバーを取得
        $Member = $this->memberRepository->find($member_id);
        if (!$Member) {
            log_info('JsysAsi 2要素認証ユーザー編集 存在しないメンバー', [
                'member_id' => $member_id,
            ]);
            throw new NotFoundHttpException(trans(
                'jsys_asi.admin.tfa_user.edit.member.not_found'
            ));
        }

        // 2要素認証ユーザーを取得
        $tfaUser = $this->jsysAsiTfaUserRepository->findOneBy(['member_id' => $member_id]);
        if (!$tfaUser) {
            // 2要素認証ユーザーに未登録のメンバーは無効状態で新規登録
            log_info('JsysAsi 2要素認証ユーザー編集 登録開始', [
                'member_id' => $member_id,
            ]);

            // シークレット・パスワード・ソルトを生成、シークレットを暗号化
            $secret    = $this->jsysAsiTfaService->generateSecret();
            $password  = $this->jsysAsiCryptService->createPassword();
            $salt      = $this->jsysAsiCryptService->createSalt();
            $encrypted = $this->jsysAsiCryptService->encrypt(
                $secret,
                $password,
                $salt
            );

            // 登録
            $tfaUser = new JsysAsiTfaUser();
            $tfaUser
                ->setMemberId($member_id)
                ->setEnabled(false)
                ->setSecret($encrypted)
                ->setSecretPassword($password)
                ->setSecretSalt($salt)
                ->setCreateDate(new \DateTime())
                ->setUpdateDate(new \DateTime());

            $this->entityManager->persist($tfaUser);
            $this->entityManager->flush($tfaUser);

            log_info('JsysAsi 2要素認証ユーザー編集 登録完了', [
                'id'        => $tfaUser->getId(),
                'member_id' => $member_id,
            ]);
        }

        $form = $this->formFactory
            ->createBuilder(JsysAsiTfaUserType::class, $tfaUser)
            ->getForm();

        // 入力されたOTPに問題が無ければ有効化
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            log_info('JsysAsi 2要素認証ユーザー編集 有効化開始', [
                'id'        => $tfaUser->getId(),
                'member_id' => $member_id,
            ]);

            $tfaUser = $form->getData();
            $tfaUser
                ->setEnabled(true)
                ->setUpdateDate(new \DateTime());

            $this->entityManager->persist($tfaUser);
            $this->entityManager->flush($tfaUser);

            log_info('JsysAsi 2要素認証ユーザー編集 有効化完了', [
                'id'        => $tfaUser->getId(),
                'member_id' => $member_id,
            ]);
            $this->addSuccess('admin.common.save_complete', 'admin');

            // 一覧へリダイレクト
            return $this->redirect($this->generateUrl('admin_jsys_asi_tfa_user'));
        }

        return [
            'form'   => $form->createView(),
            'Member' => $Member,
            'QRCode' => $this->jsysAsiTfaService->getQRCode($tfaUser),
        ];
    }

    /**
     * 2要素認証ユーザーの削除を行います。
     * @param Request $request
     * @param JsysAsiTfaUser $JsysAsiTfaUser
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route(
     *   "/%eccube_admin_route%/jsys_asi_tfa_user/{id}/delete",
     *   requirements={"id" = "\d+"},
     *   name="admin_jsys_asi_tfa_user_delete",
     *   methods={"DELETE"}
     * )
     */
    public function delete(Request $request, JsysAsiTfaUser $JsysAsiTfaUser)
    {
        $this->isTokenValid();

        log_info('JsysAsi 2要素認証ユーザー 削除開始', [$JsysAsiTfaUser->getId()]);

        try {
            $this->entityManager->remove($JsysAsiTfaUser);
            $this->entityManager->flush($JsysAsiTfaUser);

            $this->addSuccess('admin.common.delete_complete', 'admin');
            log_info('JsysAsi 2要素認証ユーザー 削除成功', [$JsysAsiTfaUser->getId()]);

        } catch (\Exception $ex) {
            log_info('JsysAsi 2要素認証ユーザー 削除失敗', [
                $JsysAsiTfaUser->getId(),
                $ex
            ]);
            $message = trans('admin.common.delete_error');
            $this->addError($message, 'admin');
        }

        // 一覧へリダイレクト
        return $this->redirectToRoute('admin_jsys_asi_tfa_user');
    }

}
