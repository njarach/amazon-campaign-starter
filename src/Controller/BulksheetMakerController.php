<?php

namespace App\Controller;

use App\DTO\Bulksheet;
use App\Entity\BulksheetRecord;
use App\Entity\User;
use App\Form\BulksheetType;
use App\Repository\BulksheetRecordRepository;
use App\Service\CampaignBulksheetMakerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BulksheetMakerController extends AbstractController
{
    public function __construct(private readonly CampaignBulksheetMakerService $bulksheetMakerService) {}

    #[Route('/bulksheet/create', name: 'app_create_bulksheet', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(BulksheetType::class);
        $form->handleRequest($request);

        $data = $form->getData();

        $keywords = array_values(array_filter(array_map(
            fn($k) => $k['text'] ?? null,
            $data['keywords']
        )));

        $bulksheet = new Bulksheet();
        $bulksheet->setAsin($data['asin']);
        $bulksheet->setCampaignId($data['campaignId']);
        $bulksheet->setAutobid((float) $data['autobid']);
        $bulksheet->setSku($data['sku'] ?? '');
        $bulksheet->setKeywords($keywords);

        $bulksheet = $this->bulksheetMakerService->generateCampaigns($bulksheet);

        /** @var User $user */
        $user = $this->getUser();

        $record = new BulksheetRecord();
        $record->setUser($user);
        $record->setAsin($data['asin']);
        $record->setCampaignId($data['campaignId']);
        $record->setKeywords($keywords);
        $em->persist($record);
        $em->flush();

        $filepath = $this->getParameter('kernel.project_dir') . '/var/tmp/amazon_campaign.csv';
        $this->bulksheetMakerService->exportToCsv($bulksheet, $filepath);

        return $this->file($filepath, 'adlance_campaign_' . date('Ymd_His') . '.csv');
    }

    #[Route('/history', name: 'app_history', methods: ['GET'])]
    public function history(BulksheetRecordRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('bulksheet_maker/history.html.twig', [
            'records' => $repository->findByUserOrderedByDate($user),
        ]);
    }

    #[Route('/history/{id}', name: 'app_history_show', methods: ['GET'])]
    public function show(BulksheetRecord $record): Response
    {
        return $this->render('bulksheet_maker/show.html.twig', [
            'record' => $record,
        ]);
    }

    #[Route('/history/delete/{id}', name: 'app_history_delete', methods: ['DELETE'])]
    public function delete(Request $request, BulksheetRecord $record, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $record->getId(), $request->request->get('_token'))) {
            $em->remove($record);
            $em->flush();
        }
        $this->addFlash('success', 'Entrée supprimée.');
        return $this->redirectToRoute('app_history');
    }

    #[Route('/history/{id}/download', name: 'app_history_download', methods: ['GET'])]
    public function download(BulksheetRecord $record): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($record->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $bulksheet = new Bulksheet();
        $bulksheet->setAsin($record->getAsin());
        $bulksheet->setCampaignId($record->getCampaignId());
        $bulksheet->setAutobid(0.35);
        $bulksheet->setSku('');
        $bulksheet->setKeywords($record->getKeywords());

        $bulksheet = $this->bulksheetMakerService->generateCampaigns($bulksheet);

        $filepath = $this->getParameter('kernel.project_dir') . '/var/tmp/amazon_campaign.csv';
        $this->bulksheetMakerService->exportToCsv($bulksheet, $filepath);

        return $this->file($filepath, 'adlance_campaign_' . $record->getAsin() . '.csv');
    }
}
