<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Form\RecipeType;
use App\Repository\IngredientRepository;
use App\Repository\RecipeRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Ingredient;
use App\Form\IngredientType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class RecipeController extends AbstractController
{
    /**
     * Ce controller montre un formulaire qui nous permet de créer un ingrédient
     * et de l'envoyer en BDD
     * @param PaginatorInterface $paginator
     * @param RecipeRepository $repository
     * @param Response $request
     * @return Response
     */
    #[isGranted('ROLE_USER')]
    #isGranted nous permet de restreindre la Route au user avec comme role 'ROLE_USER'
    #[Route('/recette', name: 'recipe.index',methods: ['GET'])]
    public function index(PaginatorInterface $paginator
        ,RecipeRepository $repository
        , Request $request
    ): Response
    {
        $recipes = $paginator->paginate(
        #FindBy pour afficher uniquement les recette lié a l'utilisateur connecté
        $repository->findBy(['user'=>$this->getUser()]),
            $request->query->getInt('page', 1), /*page number*/
            10 ); /*limit per page*/



        return $this->render('pages/recipe/index.html.twig', [
            'recipes' => $recipes
        ]);
    }

    /**
     * Cette fonction nous permet de créer une nouvelle recette et de l'ajouté en BDD
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[isGranted('ROLE_USER')]
    #isGranted nous permet de restreindre la Route au user avec comme role 'ROLE_USER'
    #[Route('/recette/creation', 'recipe.new', methods: ['GET' , 'POST'])]
    public function new(Request $request, EntityManagerInterface $manager) : Response
    {
        #On instencie un objet de recipe
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $recipe = $form->getData();
            #Ici lors de la création d'une recette on lui injecte le User qui la créer
            $recipe->setUser($this->getUser());
            #Envoie en base (commit)
            $manager->persist($recipe);
            #Enregistrement en base de données(push)
            $manager->flush();

            $this->addFlash(
                'success',
                'Votre recette a été ajouté avec succès !'
            );

            return $this->redirectToRoute('recipe.index');
        }

        return $this->render('pages/recipe/new.html.twig' ,
            [
                'form' => $form->createView()
            ]);
    }

    /**
     * Modification de la recette et envoie en BDD
     * @param Recipe $recipe
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[IsGranted(
        new Expression('is_granted("ROLE_USER") and user === subject.getUser()'),
        subject: 'recipe',
    )]
    #Ici en plus de restreindre a un comptr connecté il faut aussi que la recette soit lié au user
    #[Route('/recette/edition/{id}','recipe.edit', methods: ['GET', 'POST'])]
    public function edit(Recipe $recipe , Request $request, EntityManagerInterface $manager) : Response
    {

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $recipe = $form->getData();
            #Envoie en BDD (commit)
            $manager->persist($recipe);
            #Enregistrement en BDD(push)
            $manager->flush();

            $this->addFlash(
                'success',
                'Votre recette à été modifié avec succès !'
            );
            #On envoie le flashMessage dans le ingredient.index
            return $this->redirectToRoute('recipe.index');
        }


        return $this->render('pages/recipe/edit.html.twig', [
            'form'=>$form->createView()
        ]);
    }

    /**
     * Suppression d'un ingrédient
     * @param EntityManagerInterface $manager
     * @param Recipe $recipe
     * @return Response
     */
    #[Route('/recette/delete/{id}', 'recipe.delete', methods: ['GET'])]
    public function delete(EntityManagerInterface $manager, Recipe $recipe) : Response
    {
        $manager->remove($recipe);
        $manager->flush();

        $this->addFlash(
            'success',
            'Votre ingrédient à été supprimé avec succès !'
        );

        return $this->redirectToRoute('recipe.index');
    }
}
