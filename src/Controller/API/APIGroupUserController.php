<?php

namespace App\Controller\API;

use App\Entity\GroupUsers;
use App\Entity\User;
use App\Form\GroupUsersType;
use App\Repository\GroupUsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class APIGroupUserController extends AbstractController
{
  private $em;
  private $serializer;
  private $validator;


    public function __construct(EntityManagerInterface $entityManager,SerializerInterface $serializer,ValidatorInterface $validator){

        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator ;

    }

  /**
   * @Route("/api/v1/group", name="groupUser_get", methods={"POST"})
   *
   * @SWG\Response(
   *     response="200",
   *     description="success",
   *)
   * @SWG\Parameter(
   *     name="id",
   *     type="integer",
   *     in="query",
   *     required=true,
   * )
   */
    public function index(Request $request)
    {
          $id = $request->query->get('id');
          $group = $this->em->getRepository(GroupUsers::class)->find($id);
          $data = $this->serializer->serialize($group, 'json');

          return new Response($data, 200, [
            'Content-Type' => 'application/json'
          ]);
    }

  /**
   * @Route("api/v1/group/new",name="groupUser_new", methods={"POST"})
   *
   * @SWG\Response(
   *     response="200",
   *     description="success",
   *)
   * @SWG\Parameter(
   *     name="name",
   *     type="string",
   *     in="query",
   *     required=true,
   * )
   */
  public function new (Request $request){
      $name = $request->query->get('name');
      $group = new GroupUsers();
    if(empty($this->getUser())){
      $data = $this->serializer->serialize(array('message'=>'not connected'), 'json');
      return new Response($data, 503, [
        'Content-Type' => 'application/json'
      ]);
    }
      $group->setCreatorId($this->getUser()->getId());
      $group->setName($name);
      $group->addUser($this->getUser());
      $error = $this->validator->validate($group);
      if(count($error)){
          $error = $this->serializer->serialize($error,'json');
          return new Response($error, 500, [
              'Content-Type' => 'application/json'
          ]);
      }
      $this->em->persist($group);
      $this->em->flush();
      $data = $this->serializer->serialize(array('message'=>'OK'), 'json');
        return new Response($data, 200, [
          'Content-Type' => 'application/json'
        ]);

  }

  /**
   * @Route("api/v1/group/delete", name="groupUser_delete", methods={"DELETE"})
   * @SWG\Response(
   *     response="200",
   *     description="success",
   *)
   * @SWG\Parameter(
   *     name="id",
   *     type="integer",
   *     in="query",
   *     required=true,
   * )
   */
  public function delete (Request $request)
  {
      $id = $request->query->get('id');
    $group = $this->em->getRepository(GroupUsers::class)->find($id);
    if(empty($group)){
      $data = $this->serializer->serialize(array('message'=>'Empty Data'), 'json');
      return new Response($data, 400, [
        'Content-Type' => 'application/json'
      ]);
    }

    $this->em->remove($group);
    $this->em->flush();
    $data = $this->serializer->serialize(array('message'=>'OK'), 'json');
    return new Response($data, 200, [
      'Content-Type' => 'application/json'
    ]);
  }


  /**
   * @Route("api/v1/group/update/name",name="groupUser_update_name",methods={"POST"})
   *  @SWG\Response(
   *     response="200",
   *     description="success",
   *)
   * @SWG\Parameter(
   *     name="name",
   *     type="string",
   *     in="query",
   *     required=true,
   * )
   * @SWG\Parameter(
   *     name="id",
   *     type="integer",
   *     in="query",
   *     required=true,
   * )
   */
  public function updateName(Request $request){
    $id = $request->query->get('id');
    $name = $request->query->get('name');
    $group = $this->em->getRepository(GroupUsers::class)->find($id);
    $group->setName($name);
      $error = $this->validator->validate($group);
      if(count($error)){
          $error = $this->serializer->serialize($error,'json');
          return new Response($error, 500, [
              'Content-Type' => 'application/json'
          ]);
      }
    $this->em->persist($group);
    $this->em->flush();
    $data = $this->serializer->serialize(array('message'=>'OK'), 'json');
    return new Response($data, 200, [
      'Content-Type' => 'application/json'
    ]);

  }

  /**
   * @Route("api/v1/group/update/creator",name="groupUser_update_creator",methods={"PUT"})
   * @SWG\Response(
   *     response="200",
   *     description="success",
   *)
   * @SWG\Parameter(
   *     name="id",
   *     type="integer",
   *     in="query",
   *     required=true,
   * )
   * @SWG\Parameter(
   *     name="creator",
   *     type="integer",
   *     in="query",
   *     required=true,
   * )
   */
  public function updateCreator(Request $request){
      $id = $request->query->get('id');
      $creator = $request->query->get('creator');
    $group = $this->em->getRepository(GroupUsers::class)->find($id);
    $is_in_group = false;
    foreach ($group->getUsers() as $key=>$value ){
        if ($value->getId() == $creator){
            $is_in_group = true;
        }
    }
    if (!$is_in_group){
        $error = $this->serializer->serialize(array('error'=>'this user is not in group '),'json');

        return new Response($error, 500, [
            'Content-Type' => 'application/json'
        ]);
    }
    $group->setCreatorId($creator);
      $error = $this->validator->validate($group);
      if(count($error)){
          $error = $this->serializer->serialize($error,'json');
          return new Response($error, 500, [
              'Content-Type' => 'application/json'
          ]);
      }
    $this->em->persist($group);
    $this->em->flush();
    $data = $this->serializer->serialize(array('message'=>'OK'), 'json');
    return new Response($data, 200, [
      'Content-Type' => 'application/json'
    ]);
  }

  /**
   * @Route("api/v1/group/add/user",name="groupUser_add_user",methods={"PATCH"})
   * @SWG\Response(
   *     response="200",
   *     description="success",
   *)
   * @SWG\Parameter(
   *     name="id",
   *     type="integer",
   *     in="query",
   *     required=true,
   * )
   * @SWG\Parameter(
   *     name="user_id",
   *     type="integer",
   *     in="query",
   *     required=true,
   * )
   */
  public function newUser(Request $request)
  {
    $id = $request->query->get('id');
    $user_id = $request->query->get('user_id');
    $group = $this->em->getRepository(GroupUsers::class)->find($id);
    $user = $this->em->getRepository(User::class)->find($user_id);
    $group->addUser($user);
      $error = $this->validator->validate($group);
      if(count($error)){
          $error = $this->serializer->serialize($error,'json');
          return new Response($error, 500, [
              'Content-Type' => 'application/json'
          ]);
      }
    $this->em->persist($group);
    $this->em->flush();
    $data = $this->serializer->serialize(array('message'=>'OK'), 'json');
    return new Response($data,200, [
      'Content-Type' => 'application/json'
    ]);
  }

  /**
   * @Route("api/v1/group/delete/user",name="groupUser_delete_user",methods={"DELETE"})
   * @SWG\Response(
   *     response="200",
   *     description="success",
   *)
   * @SWG\Parameter(
   *     name="id",
   *     type="integer",
   *     in="query",
   *     required=true,
   * )
   * @SWG\Parameter(
   *     name="user_id",
   *     type="integer",
   *     in="query",
   *     required=true,
   * )
   */
  public function deleteUser(Request $request)
  {
    $id = $request->query->get('id');
    $user_id = $request->query->get('user_id');
    $group = $this->em->getRepository(GroupUsers::class)->find($id);
    $user = $this->em->getRepository(User::class)->find($user_id);

    if($this->getUser()->getId() != $group->getCreatorId() && $this->getUser()->getId() != $user_id ){
      $data = $this->serializer->serialize(array('message'=>'you are not admin'), 'json');
      return new Response($data, 403, [
        'Content-Type' => 'application/json'
      ]);
    }
    if($user_id == $group->getCreatorId() && count($group->getUsers()) <= 1){
        $this->em->remove($group);
        $this->em->flush();
        $data = $this->serializer->serialize(array('message'=>'group delete 0 users'), 'json');
        return new Response($data, 200, [
            'Content-Type' => 'application/json'
        ]);
    }


    $group->removeUser($user);
    if($user_id == $group->getCreatorId() && count($group->getUsers())  > 1){
          $group->setCreatorID($group->getUsers()[0]->getId());

    }
    $this->em->persist($group);
    $this->em->flush();
    $data = $this->serializer->serialize(array('message'=>'OK'), 'json');
    return new Response($data, 200, [
      'Content-Type' => 'application/json'
    ]);
  }

}
