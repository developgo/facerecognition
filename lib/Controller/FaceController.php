<?php
namespace OCA\FaceRecognition\Controller;

use OCP\IRequest;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Controller;

use OCA\FaceRecognition\Db\Face;
use OCA\FaceRecognition\Db\FaceMapper;

use OCA\FaceRecognition\Db\FaceNew;
use OCA\FaceRecognition\Db\FaceNewMapper;

use OCA\FaceRecognition\Db\Image;
use OCA\FaceRecognition\Db\ImageMapper;

class FaceController extends Controller {

	private $rootFolder;
	private $faceMapper;
	private $faceNewMapper;
	private $imageMapper;
	private $userId;

	public function __construct($AppName, IRequest $request, IRootFolder $rootFolder, FaceMapper $facemapper, FaceNewMapper $facenewmapper, ImageMapper $imagemapper, $UserId) {
		parent::__construct($AppName, $request);
		$this->rootFolder = $rootFolder;
		$this->faceMapper = $facemapper;
		$this->faceNewMapper = $facenewmapper;
		$this->imageMapper = $imagemapper;
		$this->userId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getThumb ($id, $size) {
		$face = $this->faceNewMapper->find($id);
		$image = $this->imageMapper->find($this->userId, $face->getImage());
		$fileId = $image->getFile();

		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$nodes = $userFolder->getById($fileId);
		$file = $nodes[0];

		$ownerView = new \OC\Files\View('/'. $this->userId . '/files');
		$path = $userFolder->getRelativePath($file->getPath());

		$img = new \OC_Image();
		$fileName = $ownerView->getLocalFile($path);
		$img->loadFromFile($fileName);

		$x = $face->getLeft ();
		$y = $face->getTop ();
		$w = $face->getRight () - $x;
		$h = $face->getBottom () - $y;

		$padding = $h*0.25;
		$x -= $padding;
		$y -= $padding;
		$w += $padding*2;
		$h += $padding*2;

		$img->crop($x, $y, $w, $h);
		$img->scaleDownToFit($size, $size);

		$resp = new DataDisplayResponse($img->data(), Http::STATUS_OK, ['Content-Type' => $img->mimeType()]);
		$resp->setETag((string)crc32($img->data()));
		$resp->cacheFor(7 * 24 * 60 * 60);
		$resp->setLastModified(new \DateTime('now', new \DateTimeZone('GMT')));

		return $resp;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 */
	public function invalidate($id) {
		$face = $this->faceMapper->find($id, $this->userId);
		$note->setDistance(1.0);
		$newFace = $this->faceMapper->update($face);
		return new DataResponse($newFace);
	}

}
