<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Validator;

use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;

use wapmorgan\MediaFile\MediaFile;

use App\Models\VideoPost;
use App\Http\Resources\AudioPostCollection;
use App\Traits\Interactable;
use App\Models\VideoSrc;

class VideoPostController extends Controller
{
    use Interactable;
    public $shouldTransrcibe = false;

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'string|required|max:255',
            // 'src_url' => 'string|required|max:255',
            'full_text' => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'church_id' => 'nullable|integer|exists:churches,id',
            'size' => 'nullable|integer',
            'length' => 'nullable|integer',
            'language' => 'nullable|string',
            'address_id' => 'nullable|integer|exists:addresses,id',
            'video' => 'required',
            'video.*' => 'mimes:mp4,mkv,avi,mov'
        ]);

        $data = collect($request->all())->toArray();
        $userId = Auth::id();
        $data['user_id'] = $userId;
        $data['poster_id'] = $userId;
        $data['poster_type'] = 'user';

        $video  = base64_decode($request['video']);
        // $videoFile = Storage($video);
        //TODO: parse the video extension from the base64encoded file
        $name = time() . '.mp4';

        $fileMoved = Storage::disk('public')->put('video/full/' . $name, $video);
        $path = 'video/full/' . $name;
        $data['src_url'] =  Storage::disk('public')->url($path);
        $data['size'] = Storage::disk('public')->size($path);

        $details = $this->getTrackDetails($path);

        $data['length']= $details['length'];

        $videoPost = VideoPost::create($data);

        $src = VideoSrc::create(['length' => $details['length'], 'format' => 'mp4', 'src' => $data['src_url'], 'quality' => 1, 'size' => $data['size'], 'video_post_id' => $videoPost->id,]);

        $interacted = $this->saveRelated($data, $videoPost);
     
        $result = VideoPost::with(['srcs',  'poster', 'user'])
        ->with('hierarchies', 'addresses', 'tags', 'images', 'comments', 'churches')
        ->withCount([
            'comments',
            'likes',
            'likes as liked' => function (Builder $query) use ($userId) {
                $query->where('user_id', $userId);
            },
            'views',
            'views as viewed' => function (Builder $query) use ($userId) {
                $query->where('user_id', $userId);
            },
        ])
        ->find($videoPost->id);

        if ($videoPost) {
            return response()->json(['data' => $result], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    public function getTrackDetails(String $path): array
    {
        $storagePath = storage_path('app/public/' . $path);
        try {
            $media = MediaFile::open($storagePath);
            // for audio
            if ($media->isAudio()) {
                $audio = $media->getAudio();
                return [
                    'length' => $audio->getLength(),
                    'bitrate' => $audio->getBitRate(),
                    'refresh_rate' => $audio->getSampleRate(),
                    'channels' => $audio->getChannels(),
                ];
            }
            // for video
            else {
                $video = $media->getVideo();
                // calls to VideoAdapter interface
                return [
                    'length' => $video->getLength(),
                    // 'dimensions' => $video->getWidth() . 'x' . $video->getHeight(),
                    // 'frame_rate' => $video->getFramerate(),
                ];
            }
        } catch (wapmorgan\MediaFile\Exceptions\FileAccessException $e) {
            // FileAccessException throws when file is not a detected media
        } catch (wapmorgan\MediaFile\Exceptions\ParsingException $e) {
            echo 'File is propably corrupted: ' . $e->getMessage() . PHP_EOL;
        }
        print('gettting track details');
    }


    public function related(Request $request)
    {
        $audio = VideoPost::find($request['id']);
        //TODO: here use search plugin to list advanced related
        $names = explode(' ', $audio->name);
        $audia = VideoPost::where('name', 'like', $audio->name);
        foreach ($names as $key => $name) {
            $audia->orWhere('name', 'like', "%$name%");
            $audia->orWhere('description', 'like', "%$name%");
        }
        $data = $audia->with('hierarchies')
            ->whereNot('audio_posts.id', $audio->id)->paginate();
        return response()->json($data);
    }


    public function getTrackFullText(VideoPost $audio): VideoPost
    {
        if ($this->shouldTransrcibe) {
            //connect to google
            $content = file_get_contents($audio->src_url);
            $googleAudio = (new RecognitionAudio())->setContent($content);

            # The audio file's encoding, sample rate and language
            $config = new RecognitionConfig([
                'encoding' => AudioEncoding::LINEAR16,
                'sample_rate_hertz' => 32000,
                'language_code' => 'en-US'
            ]);

            # Instantiates a client
            $client = new SpeechClient();
            # Detects speech in the audio file
            $response = $client->recognize($config, $googleAudio);
            # Print most likely transcription
            foreach ($response->getResults() as $result) {
                $alternatives = $result->getAlternatives();
                $mostLikely = $alternatives[0];
                $transcript = $mostLikely->getTranscript();
                printf('Transcript: %s' . PHP_EOL, $transcript);
            }
            $client->close();
            //change audio to text
            //save it then return object
        }
        return $audio;
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'integer|required|exists:audio_posts,id',
            'name' => 'string|required|max:255',
            // 'src_url' => 'string|required|max:255',
            'full_text' => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'church_id' => 'nullable|integer|exists:churches,id',
            'size' => 'nullable|integer',
            'length' => 'nullable|integer',
            'language' => 'nullable|string',
            'address_id' => 'nullable|integer|exists:addresses,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $data = collect($request->all())->toArray();
        $data['user_id'] = Auth::user()->id;
        $id = $request->route('id');
        $result = VideoPost::find($id);
        //update result
        $result = $this->getTrackDetails($result);
        $result = $this->getTrackFullText($result);
        $result = $result->update($data);


        if ($result) {
            return response()->json(['data' => $result], 201);
        } else {
            return response()->json(['data' => false, 'errors' => 'unknown error occured'], 400);
        }
    }

    public function get(Request $request)
    {
        $id = (int)$request->route('id');
        $userId = Auth::user()->id;
        if ($audio = VideoPost::with(['srcs', 'comments', 'images', 'user', 'churches', 'addresses'])
            ->withCount([
                'comments',
                'likes',
                'likes as liked' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
                'views',
                'views as viewed' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])
            ->find($id)
        ) {
            return response()->json([
                'data' => $audio
            ], 200);
        } else {
            return response()->json([
                'data' => false
            ], 404);
        }
    }

    public function list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'nullable|string|min:3'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }
        $userId = Auth::id();

        $query = $request['q'];
        $audia = VideoPost::with(['images', 'user', 'poster'])
            ->withCount([
                'comments',
                'likes',
                'likes as liked' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
                'views',
                'views as viewed' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ]);
        if ($query) {
            $audia = $audia->search($query);
        }
        //here insert search parameters and stuff
        $length = (int)(empty($request['perPage']) ? 15 : $request['perPage']);
        $audia = $audia->paginate($length);
        $data = new AudioPostCollection($audia);
        return response()->json($data);
    }


    public function delete(Request $request)
    {
        $id = (int)$request->route('id');
        if ($audio = VideoPost::find($id)) {
            $audio->delete();
            return response()->json([
                'data' => true
            ], 200);
        } else {
            return response()->json([
                'data' => false
            ], 404);
        }
    }
}
