<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\AudioPost;
use App\Models\AudioSrc;
use App\Models\Church;
use App\Models\Comment;
use App\Models\Devotional;
use App\Models\Event;
use App\Models\Feed;
use App\Models\Image;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Models\VideoPost;
use App\Models\VideoSrc;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Adds a coherent, explicitly local demo library for development.
 *
 * The records use fictional example profiles and original sample copy. They
 * are meant to make the mobile feed useful without presenting the content as
 * an official publication or a real endorsement.
 */
class FaithContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'imageables',
            'addressables',
            'taggables',
            'churchables',
            'hierarchyables',
            'event_user',
            'devotional_user',
        ] as $pivotTable) {
            DB::table($pivotTable)->truncate();
        }

        $pastors = $this->seedPastors();
        $addresses = $this->seedAddresses($pastors);
        $churches = $this->seedChurches($pastors, $addresses);
        $this->seedMedia($pastors, $churches, $addresses);
        $this->seedPosts($pastors, $churches, $addresses);
        $this->seedDevotionals($pastors, $churches);
        $this->seedEvents($pastors, $churches, $addresses);
        $this->seedFeeds($pastors);
    }

    /**
     * @return array<string, User>
     */
    private function seedPastors(): array
    {
        $profiles = [
            [
                'key' => 'daniel',
                'name' => 'Pastor Daniel Okonkwo',
                'email' => 'daniel.okonkwo@example.test',
                'gender' => 'M',
                'city' => 'Lagos',
                'verse' => '“The LORD is my shepherd; I shall not want.” — Psalm 23:1',
                'bio' => 'Demo profile for a Nigerian pastor who teaches practical faith, family stewardship, and the grace of Christ.',
            ],
            [
                'key' => 'grace',
                'name' => 'Pastor Grace Adebayo',
                'email' => 'grace.adebayo@example.test',
                'gender' => 'F',
                'city' => 'Ibadan',
                'verse' => '“Those who hope in the LORD will renew their strength.” — Isaiah 40:31',
                'bio' => 'Demo profile for a Nigerian pastor who encourages women and families to serve with wisdom, prayer, and courage.',
            ],
            [
                'key' => 'samuel',
                'name' => 'Bishop Samuel Eze',
                'email' => 'samuel.eze@example.test',
                'gender' => 'M',
                'city' => 'Abuja',
                'verse' => '“Be still, and know that I am God.” — Psalm 46:10',
                'bio' => 'Demo profile for a Nigerian bishop focused on Scripture, intercession, and compassionate community leadership.',
            ],
            [
                'key' => 'michael',
                'name' => 'Pastor Michael Oyelaran',
                'email' => 'michael.oyelaran@example.test',
                'gender' => 'M',
                'city' => 'Port Harcourt',
                'verse' => '“I can do all things through Christ which strengtheneth me.” — Philippians 4:13',
                'bio' => 'Demo profile for a Nigerian pastor who shares practical messages about resilience, integrity, and caring for neighbors.',
            ],
            [
                'key' => 'emmanuel',
                'name' => 'Pastor Emmanuel Okafor',
                'email' => 'emmanuel.okafor@example.test',
                'gender' => 'M',
                'city' => 'Enugu',
                'verse' => '“Thy word is a lamp unto my feet.” — Psalm 119:105',
                'bio' => 'Demo profile for a Nigerian pastor and teacher who helps believers connect daily decisions to biblical truth.',
            ],
            [
                'key' => 'rachel',
                'name' => 'Pastor Rachel Nwosu',
                'email' => 'rachel.nwosu@example.test',
                'gender' => 'F',
                'city' => 'Kano',
                'verse' => '“Rejoice in the Lord alway.” — Philippians 4:4',
                'bio' => 'Demo profile for a Nigerian pastor who teaches gratitude, hope, and the beauty of a life anchored in Christ.',
            ],
        ];

        $pastors = [];
        foreach ($profiles as $index => $profile) {
            $pastor = User::updateOrCreate(
                ['email' => $profile['email']],
                [
                    'name' => $profile['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('ruach-demo'),
                    'description' => $profile['bio'].' '.$profile['verse'],
                    'gender' => $profile['gender'],
                    'phone' => null,
                    'avatar' => 'https://i.pravatar.cc/300?img='.(($index * 3) + 10),
                    'is_minister' => true,
                    'is_verified' => true,
                    'is_editor' => false,
                ],
            );
            $pastors[$profile['key']] = $pastor;

            $image = Image::firstOrCreate(
                ['full' => $pastor->avatar],
                [
                    'large' => $pastor->avatar,
                    'medium' => $pastor->avatar,
                    'small' => $pastor->avatar,
                    'user_id' => (string) $pastor->id,
                ],
            );
            $pastor->images()->sync([$image->id]);
        }

        // Give the demo library a small connected community graph.
        $pastorList = array_values($pastors);
        foreach ($pastorList as $pastor) {
            $relatedPastorIds = array_values(array_filter(
                [$pastorList[0]->id, $pastorList[1]->id],
                fn (int $pastorId): bool => $pastorId !== $pastor->id,
            ));
            $pastor->followers()->syncWithoutDetaching($relatedPastorIds);
        }
        $pastorIds = array_map(fn (User $pastor): int => $pastor->id, $pastorList);
        foreach ([1, 2, 3] as $viewerId) {
            $demoViewer = User::find($viewerId);
            if ($demoViewer) {
                $demoViewer->following()->syncWithoutDetaching($pastorIds);
            }
        }

        return $pastors;
    }

    /**
     * @param array<string, User> $pastors
     * @return array<string, Address>
     */
    private function seedAddresses(array $pastors): array
    {
        $addresses = [
            'lagos' => [
                'address1' => '24 Grace Covenant Way',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'postal_code' => '100001',
                'user_id' => $pastors['daniel']->id,
                'name' => 'Lagos prayer room',
                'latitude' => 6.5244,
                'longitude' => 3.3792,
            ],
            'ibadan' => [
                'address1' => '7 Scripture Crescent',
                'city' => 'Ibadan',
                'state' => 'Oyo',
                'country' => 'Nigeria',
                'postal_code' => '200001',
                'user_id' => $pastors['grace']->id,
                'name' => 'Ibadan community hall',
                'latitude' => 7.3775,
                'longitude' => 3.9470,
            ],
            'abuja' => [
                'address1' => '11 Unity Avenue',
                'city' => 'Abuja',
                'state' => 'FCT',
                'country' => 'Nigeria',
                'postal_code' => '901001',
                'user_id' => $pastors['samuel']->id,
                'name' => 'Abuja intercession centre',
                'latitude' => 9.0765,
                'longitude' => 7.3986,
            ],
            'port-harcourt' => [
                'address1' => '3 Harbor of Hope Road',
                'city' => 'Port Harcourt',
                'state' => 'Rivers',
                'country' => 'Nigeria',
                'postal_code' => '500001',
                'user_id' => $pastors['michael']->id,
                'name' => 'Port Harcourt fellowship centre',
                'latitude' => 4.8156,
                'longitude' => 7.0498,
            ],
        ];

        $result = [];
        foreach ($addresses as $key => $data) {
            $result[$key] = Address::updateOrCreate(
                ['address1' => $data['address1'], 'city' => $data['city']],
                $data,
            );
        }

        return $result;
    }

    /**
     * @param array<string, User> $pastors
     * @param array<string, Address> $addresses
     * @return array<string, Church>
     */
    private function seedChurches(array $pastors, array $addresses): array
    {
        $churches = [
            'dominion' => [
                'name' => 'Dominion City Bible Church',
                'alternate_name' => 'DCBC',
                'slogan' => 'The Word that anchors us',
                'description' => 'A local demo church community in Lagos, centered on the Word, prayer, and service.',
                'leader_id' => $pastors['daniel']->id,
                'user_id' => $pastors['daniel']->id,
                'address' => 'lagos',
            ],
            'living-word' => [
                'name' => 'Living Word Cathedral',
                'alternate_name' => 'LWC',
                'slogan' => 'Growing in grace and truth',
                'description' => 'A local demo cathedral in Abuja offering worship, biblical teaching, and compassionate fellowship.',
                'leader_id' => $pastors['samuel']->id,
                'user_id' => $pastors['samuel']->id,
                'address' => 'abuja',
            ],
            'grace-mountain' => [
                'name' => 'Grace Mountain Church',
                'alternate_name' => 'GMC',
                'slogan' => 'From Scripture to service',
                'description' => 'A local demo church in Ibadan where families learn to love Scripture and serve their neighbors.',
                'leader_id' => $pastors['grace']->id,
                'user_id' => $pastors['grace']->id,
                'address' => 'ibadan',
            ],
            'harbor-hope' => [
                'name' => 'Harbor of Hope Fellowship',
                'alternate_name' => 'HHF',
                'slogan' => 'A steady heart in every season',
                'description' => 'A local demo fellowship in Port Harcourt focused on hope, prayer, and practical care.',
                'leader_id' => $pastors['michael']->id,
                'user_id' => $pastors['michael']->id,
                'address' => 'port-harcourt',
            ],
        ];

        $result = [];
        foreach ($churches as $key => $data) {
            $addressKey = $data['address'];
            unset($data['address']);
            $church = Church::updateOrCreate(['name' => $data['name']], $data);
            $church->addresses()->sync([$addresses[$addressKey]->id]);
            $result[$key] = $church;
        }

        return $result;
    }

    /**
     * @param array<string, User> $pastors
     * @param array<string, Church> $churches
     * @param array<string, Address> $addresses
     */
    private function seedMedia(array $pastors, array $churches, array $addresses): void
    {
        $audio = [
            [
                'name' => 'Anchor of My Soul',
                'pastor' => 'daniel',
                'church' => 'dominion',
                'address' => 'lagos',
                'verse' => 'Psalm 46:1',
                'description' => 'A calm original worship song about trusting God as the refuge beneath every storm.',
                'lyrics' => "When the road is long, I will not fear\nYou are the anchor of my soul\nYour promises are faithful, strong\nI will sing, I will sing, I will sing\nFor the Lord is my refuge and strength",
                'url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
                'length' => 212,
            ],
            [
                'name' => 'Grace That Teaches',
                'pastor' => 'grace',
                'church' => 'grace-mountain',
                'address' => 'ibadan',
                'verse' => '2 Corinthians 12:9',
                'description' => 'A gentle reflection on the grace that meets us in weakness and teaches us to rely on Christ.',
                'lyrics' => "My grace is enough for you\nNot by strength, but grace that holds\nWhen I have nothing, I am still\nYour love sustains me through the storm",
                'url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3',
                'length' => 198,
            ],
            [
                'name' => 'Ní rere Ọ̀lọ́run',
                'pastor' => 'emmanuel',
                'church' => 'living-word',
                'address' => 'abuja',
                'verse' => 'Isaiah 40:31',
                'description' => 'A Yoruba-language worship reflection about waiting on the Lord and receiving renewed strength.',
                'lyrics' => "Ní rere Ọ̀lọ́run ni o máa gba\nLẹ́yìn àárọ̀, ọ̀nà o máa fọ́\nÌwọ ni ọ̀nà ìlù, ìbáwo ni ó máa kún\nA ó máa dúró, a ó máa sọ́wọ́",
                'url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3',
                'length' => 205,
                'language' => 'yo',
            ],
            [
                'name' => 'I Will Trust',
                'pastor' => 'michael',
                'church' => 'harbor-hope',
                'address' => 'port-harcourt',
                'verse' => 'Proverbs 3:5-6',
                'description' => 'A grounded reminder to trust God with the whole heart and let His word direct every step.',
                'lyrics' => "I will trust, I will trust You\nWith the heart I cannot see\nEvery step, You will guide me\nThis is how You hold me",
                'url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-4.mp3',
                'length' => 224,
            ],
            [
                'name' => 'The River of Peace',
                'pastor' => 'rachel',
                'church' => 'dominion',
                'address' => 'lagos',
                'verse' => 'Isaiah 33:6',
                'description' => 'A quiet worship song about the peace that wisdom makes to flow like a river.',
                'lyrics' => "There is a river of peace\nFlowing through Your Word\nWhere the heart is fixed on You\nQuietness returns\nTeach me the way I should go",
                'url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-5.mp3',
                'length' => 187,
            ],
            [
                'name' => 'When the Dawn Arrives',
                'pastor' => 'samuel',
                'church' => 'living-word',
                'address' => 'abuja',
                'verse' => 'Lamentations 3:22-23',
                'description' => 'A hopeful song for new beginnings, rooted in the unfailing love of God.',
                'lyrics' => "When the dawn arrives, You are here\nMercies greet the dawn\nThough the night was long, You are near\nNew compassion from Your throne",
                'url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-6.mp3',
                'length' => 233,
            ],
        ];

        foreach ($audio as $index => $data) {
            $pastor = $pastors[$data['pastor']];
            $post = AudioPost::updateOrCreate(
                ['name' => $data['name']],
                [
                    'src_url' => $data['url'],
                    'full_text' => "[ti:{$data['name']}]\n[ar:{$pastor->name}]\n\n{$data['lyrics']}",
                    'description' => $data['description'].' Scripture: '.$data['verse'],
                    'user_id' => $pastor->id,
                    'poster_id' => $pastor->id,
                    'poster_type' => 'user',
                    'size' => 4200000 + ($index * 250000),
                    'length' => $data['length'],
                    'language' => $data['language'] ?? 'en',
                    'lyrics_status' => 'ready',
                    'media_status' => 'ready',
                ],
            );
            AudioSrc::where('audio_post_id', $post->id)->delete();
            AudioSrc::create([
                'refresh_rate' => 44100,
                'bitrate' => 128,
                'src' => $data['url'],
                'size' => $post->size,
                'length' => $data['length'],
                'format' => 'mp3',
                'quality' => 'demo',
                'variant' => 'original',
                'mime' => 'audio/mpeg',
                'status' => 'ready',
                'audio_post_id' => $post->id,
            ]);
            $post->tags()->sync($this->tagIds(['worship', $data['verse'], 'song']));
            $post->churches()->sync([$churches[$data['church']]->id]);
            $post->addresses()->sync([$addresses[$data['address']]->id]);
            $this->seedComment($post, 'audio', $pastors['rachel'], $data['verse']);
        }

        $videos = [
            [
                'name' => 'The Vine and the Branches | John 15',
                'pastor' => 'daniel',
                'church' => 'dominion',
                'address' => 'lagos',
                'verse' => 'John 15:4-5',
                'description' => 'A short visual study on abiding in Christ and bearing fruit through dependence, not effort alone.',
                'url' => 'https://storage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                'length' => 15,
            ],
            [
                'name' => 'A Morning Prayer for Nigeria | Psalm 46',
                'pastor' => 'samuel',
                'church' => 'living-word',
                'address' => 'abuja',
                'verse' => 'Psalm 46:1-3',
                'description' => 'A calm prayer for peace, wisdom, and courage as communities serve their neighbors.',
                'url' => 'https://storage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
                'length' => 15,
            ],
            [
                'name' => 'Walking by Faith | 2 Corinthians 5:7',
                'pastor' => 'grace',
                'church' => 'grace-mountain',
                'address' => 'ibadan',
                'verse' => '2 Corinthians 5:7',
                'description' => 'An encouraging visual meditation on faith, obedience, and the freedom found in Christ.',
                'url' => 'https://storage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4',
                'length' => 15,
            ],
            [
                'name' => 'The Good Shepherd | Psalm 23',
                'pastor' => 'michael',
                'church' => 'harbor-hope',
                'address' => 'port-harcourt',
                'verse' => 'Psalm 23:1-4',
                'description' => 'A reflective lesson on the Shepherd’s care through seasons of rest, challenge, and renewal.',
                'url' => 'https://storage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4',
                'length' => 15,
            ],
        ];

        foreach ($videos as $index => $data) {
            $pastor = $pastors[$data['pastor']];
            $post = VideoPost::updateOrCreate(
                ['name' => $data['name']],
                [
                    'src_url' => $data['url'],
                    'full_text' => 'Scripture focus: '.$data['verse'],
                    'description' => $data['description'],
                    'user_id' => $pastor->id,
                    'poster_id' => $pastor->id,
                    'poster_type' => 'user',
                    'size' => 8500000 + ($index * 900000),
                    'length' => $data['length'],
                    'language' => 'en',
                    'lyrics_status' => 'ready',
                    'media_status' => 'ready',
                ],
            );
            VideoSrc::where('video_post_id', $post->id)->delete();
            VideoSrc::create([
                'src' => $data['url'],
                'quality' => 720,
                'size' => $post->size,
                'length' => $data['length'],
                'format' => 'mp4',
                'dimensions' => '1280x720',
                'width' => 1280,
                'height' => 720,
                'variant' => 'original',
                'mime' => 'video/mp4',
                'status' => 'ready',
                'video_post_id' => $post->id,
            ]);
            $post->tags()->sync($this->tagIds(['teaching', $data['verse'], 'video']));
            $post->churches()->sync([$churches[$data['church']]->id]);
            $post->addresses()->sync([$addresses[$data['address']]->id]);
            $this->seedComment($post, 'video', $pastors['grace'], $data['verse']);
        }
    }

    /**
     * @param array<string, User> $pastors
     * @param array<string, Church> $churches
     * @param array<string, Address> $addresses
     */
    private function seedPosts(array $pastors, array $churches, array $addresses): void
    {
        $posts = [
            [
                'name' => 'When the Voice of God Gets Quiet',
                'pastor' => 'daniel',
                'church' => 'dominion',
                'address' => 'lagos',
                'verse' => 'Psalm 46:10',
                'body' => "Some seasons feel loud with activity and quiet with direction. Scripture gives us a better rhythm: be still, remember who God is, and take the next faithful step. Quiet is not the absence of purpose; it is often the place where purpose becomes clear.\n\nAsk for wisdom, make room for prayer, and let the peace of God guard the decisions that are already yours to make.",
            ],
            [
                'name' => 'A Simple Guide to Biblical Meditation',
                'pastor' => 'grace',
                'church' => 'grace-mountain',
                'address' => 'ibadan',
                'verse' => 'Joshua 1:8',
                'body' => "Biblical meditation is simply reading, noticing, and responding. Choose a short passage, read it slowly, ask what it teaches about God, and write one response you can practice today. The goal is not to impress anyone; it is to let truth become a living part of the ordinary day.",
            ],
            [
                'name' => 'Serving Your Community from Scripture',
                'pastor' => 'michael',
                'church' => 'harbor-hope',
                'address' => 'port-harcourt',
                'verse' => 'Galatians 6:9-10',
                'body' => "Faith becomes visible when it serves a neighbor. Start with the needs you can see: a listening ear, a practical gift, a meal, a school supply, or a sincere visit. Let Scripture shape the motive so that service is not only useful but also honest, patient, and free from pride.",
            ],
            [
                'name' => 'How to Read a Psalm',
                'pastor' => 'emmanuel',
                'church' => 'living-word',
                'address' => 'abuja',
                'verse' => 'Psalm 119:105',
                'body' => "A psalm can be read in three movements: notice the words, notice the heart behind them, and notice the response. The author may praise, confess, ask, or wait. Whatever the movement, the psalm invites an honest conversation with the God who meets us there.",
            ],
            [
                'name' => 'Testimony Is a Story, Not a Performance',
                'pastor' => 'rachel',
                'church' => 'dominion',
                'address' => 'lagos',
                'verse' => '2 Corinthians 1:10',
                'body' => "A testimony is not a contest. It is an invitation to tell the truth about what the Lord has done, so that someone else can find hope. Keep the focus on God's faithfulness, honor the people involved, and let the story point beyond yourself.",
            ],
            [
                'name' => 'A Prayer for Courage in Small Decisions',
                'pastor' => 'samuel',
                'church' => 'living-word',
                'address' => 'abuja',
                'verse' => 'James 1:5',
                'body' => "The next right step may be a conversation, a repaired relationship, or a task no one else can do for you. Ask God for wisdom, not just a perfect plan. Wisdom makes room for humility, and humility keeps love at the center of every decision.",
            ],
        ];

        foreach ($posts as $data) {
            $pastor = $pastors[$data['pastor']];
            $post = Post::updateOrCreate(
                ['title' => $data['name']],
                [
                    'body' => $data['body'].'\n\nScripture: '.$data['verse'],
                    'user_id' => $pastor->id,
                    'poster_id' => $pastor->id,
                    'poster_type' => 'user',
                ],
            );
            $post->tags()->sync($this->tagIds(['devotional', $data['verse'], 'community']));
            $post->churches()->sync([$churches[$data['church']]->id]);
            $post->addresses()->sync([$addresses[$data['address']]->id]);
            $this->seedComment($post, 'post', $pastors['daniel'], $data['verse']);
        }
    }

    /**
     * @param array<string, User> $pastors
     * @param array<string, Church> $churches
     */
    private function seedDevotionals(array $pastors, array $churches): void
    {
        $devotionals = [
            [
                'title' => 'Abide Before You Achieve',
                'pastor' => 'daniel',
                'church' => 'dominion',
                'verse' => 'John 15:4-5',
                'body' => "Fruit is evidence of branches remaining in the Vine. Before asking for a new opportunity, make room for the quieter work of abiding: listen, obey, and receive. Success that is not rooted in Christ becomes a heavy burden; dependence keeps a life connected to the One who sustains it.",
                'opening' => 'Father, make me sensitive to Your presence before I ask for a breakthrough. Teach me to remain in You.',
                'closing' => 'Lord, let every result point back to Your grace, and keep me humble as You bear fruit through me. Amen.',
            ],
            [
                'title' => 'The Mercy We Receive',
                'pastor' => 'grace',
                'church' => 'grace-mountain',
                'verse' => '2 Corinthians 12:9',
                'body' => "God's grace is not a reward for effortless lives. It is the strength to remain faithful when our own strength is spent. Receive mercy as a new beginning, then let it soften your speech, steady your work, and make room for another person's story.",
                'opening' => 'Merciful Father, receive my weakness and turn it into a place of testimony.',
                'closing' => 'May Your grace shape my home, my work, and the way I care for people today. Amen.',
            ],
            [
                'title' => 'Peace in the Midst',
                'pastor' => 'samuel',
                'church' => 'living-word',
                'verse' => 'Psalm 46:10',
                'body' => "Peace is not a promise that the noise will stop. It is the presence of God in the middle of the noise. Be still enough to remember that the nations and the heart of the earth belong to Him. Let that truth become courage for the next conversation and the next task.",
                'opening' => 'God of peace, I bring You my unsettled mind, my open hands, and my neighbors.',
                'closing' => 'May Your peace rule in my home, my work, and my city. Amen.',
            ],
            [
                'title' => 'Praying with Psalm 46',
                'pastor' => 'michael',
                'church' => 'harbor-hope',
                'verse' => 'Psalm 46:1-3',
                'body' => "Use Psalm 46 as a prayer when the situation is bigger than your plan. Ask for courage rather than control, wisdom rather than certainty, and a spirit ready to serve. The psalm does not deny the storms; it declares that God is greater than the waters.",
                'opening' => 'Lord of hosts, be my refuge as I face what I cannot control.',
                'closing' => 'Give me words that make room for peace and courage today. Amen.',
            ],
            [
                'title' => 'Wisdom for Everyday Decisions',
                'pastor' => 'emmanuel',
                'church' => 'living-word',
                'verse' => 'James 1:5',
                'body' => "Wisdom begins with a willingness to be taught. In ordinary decisions, ask what is true, who needs care, and what will become possible through obedience. Let patience do its quiet work before you make a permanent choice.",
                'opening' => 'Source of wisdom, make me attentive to the lesson hidden in this ordinary day.',
                'closing' => 'May my choices be honest, loving, and useful to the people around me. Amen.',
            ],
            [
                'title' => 'A Morning Full of Hope',
                'pastor' => 'rachel',
                'church' => 'grace-mountain',
                'verse' => 'Lamentations 3:22-23',
                'body' => "Mercies appear in small and large ways: a safe journey, a kind word, a renewed strength, a door that opens. Receive them as reminders that God has not abandoned His people. Hope gives the ordinary day a wider horizon.",
                'opening' => 'Faithful Father, thank You for the mercies already surrounding me.',
                'closing' => 'Let me carry hope into the people and places I touch today. Amen.',
            ],
        ];

        foreach ($devotionals as $index => $data) {
            $pastor = $pastors[$data['pastor']];
            $devotional = Devotional::updateOrCreate(
                ['title' => $data['title']],
                [
                    'user_id' => $pastor->id,
                    'poster_id' => $pastor->id,
                    'poster_type' => 'user',
                    'day' => now()->subDays($index)->setTime(5, 30),
                    'opening_prayer' => $data['opening'],
                    'closing_prayer' => $data['closing'],
                    'memory_verse' => $data['verse'],
                    'body' => $data['body'],
                ],
            );
            $devotional->tags()->sync($this->tagIds(['devotional', $data['verse'], 'prayer']));
            $devotional->churches()->sync([$churches[$data['church']]->id]);
            $devotional->devotees()->syncWithoutDetaching([$pastor->id]);
        }
    }

    /**
     * @param array<string, User> $pastors
     * @param array<string, Church> $churches
     * @param array<string, Address> $addresses
     */
    private function seedEvents(array $pastors, array $churches, array $addresses): void
    {
        $events = [
            [
                'name' => 'Mornings of Intercession | Psalm 46',
                'pastor' => 'samuel',
                'church' => 'living-word',
                'address' => 'abuja',
                'verse' => 'Psalm 46:10',
                'description' => 'A peaceful morning of Scripture, intercession, and prayer for Nigeria, our families, and our communities.',
                'days' => 3,
            ],
            [
                'name' => 'Bible Study: The Vine and the Branches',
                'pastor' => 'daniel',
                'church' => 'dominion',
                'address' => 'lagos',
                'verse' => 'John 15:1-17',
                'description' => 'A practical study of abiding, fruitfulness, and love in the life of a disciple.',
                'days' => 6,
            ],
            [
                'name' => 'Community Harvest and Gratitude',
                'pastor' => 'grace',
                'church' => 'grace-mountain',
                'address' => 'ibadan',
                'verse' => 'Psalm 100:4',
                'description' => 'A local gathering for worship, stories of gratitude, and sharing resources with neighbors in need.',
                'days' => 12,
            ],
            [
                'name' => "Pastors' Prayer Summit: Unity in Nigeria",
                'pastor' => 'michael',
                'church' => 'harbor-hope',
                'address' => 'port-harcourt',
                'verse' => '1 Timothy 2:8',
                'description' => 'A respectful day of prayer for unity, wisdom, and the wellbeing of communities across Nigeria.',
                'days' => 18,
            ],
            [
                'name' => 'Midnight Worship: Jesus, You Are the Anchor',
                'pastor' => 'rachel',
                'church' => 'dominion',
                'address' => 'lagos',
                'verse' => 'Hebrews 12:2',
                'description' => 'A quiet, Scripture-centered worship night for people seeking rest and renewed hope.',
                'days' => 25,
            ],
            [
                'name' => 'Young Believers Scripture Circle',
                'pastor' => 'emmanuel',
                'church' => 'living-word',
                'address' => 'abuja',
                'verse' => '2 Timothy 2:2',
                'description' => 'An open discussion for young believers learning to practice faith, character, and healthy accountability.',
                'days' => 31,
            ],
        ];

        foreach ($events as $data) {
            $pastor = $pastors[$data['pastor']];
            $start = now()->addDays($data['days'])->setTime(9, 0);
            $event = Event::updateOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'].' Scripture: '.$data['verse'],
                    'starting_at' => $start,
                    'ending_at' => $start->copy()->addHours(3),
                    'user_id' => $pastor->id,
                    'poster_id' => $pastor->id,
                    'poster_type' => 'user',
                ],
            );
            $event->tags()->sync($this->tagIds(['event', $data['verse'], 'prayer']));
            $event->churches()->sync([$churches[$data['church']]->id]);
            $event->addresses()->sync([$addresses[$data['address']]->id]);
            $event->attendees()->syncWithoutDetaching([
                $pastor->id,
                $pastors['grace']->id,
            ]);
            $this->seedComment($event, 'event', $pastors['emmanuel'], $data['verse']);
        }
    }

    /**
     * @param array<string, User> $pastors
     */
    private function seedFeeds(array $pastors): void
    {
        Feed::truncate();
        $items = [
            ['type' => 'audio', 'id' => AudioPost::query()->orderByDesc('id')->value('id'), 'user' => 'daniel'],
            ['type' => 'video', 'id' => VideoPost::query()->orderByDesc('id')->value('id'), 'user' => 'grace'],
            ['type' => 'post', 'id' => Post::query()->orderByDesc('id')->value('id'), 'user' => 'samuel'],
            ['type' => 'event', 'id' => Event::query()->orderByDesc('id')->value('id'), 'user' => 'michael'],
            ['type' => 'audio', 'id' => AudioPost::query()->orderBy('id')->value('id'), 'user' => 'rachel'],
            ['type' => 'post', 'id' => Post::query()->orderBy('id')->value('id'), 'user' => 'emmanuel'],
        ];
        foreach ($items as $item) {
            if (!$item['id']) {
                continue;
            }
            Feed::create([
                'parentable_type' => $item['type'],
                'parentable_id' => $item['id'],
                'postable_type' => 'user',
                'postable_id' => $pastors[$item['user']]->id,
            ]);
        }
    }

    /**
     * @return array<int, int>
     */
    private function tagIds(array $names): array
    {
        return array_values(array_map(
            fn (string $name): int => Tag::firstOrCreate(['tag' => $name])->id,
            $names,
        ));
    }

    private function seedComment(object $model, string $type, User $author, string $verse): void
    {
        $comment = 'A useful reminder to keep reading, praying, and sharing the hope of '.$verse.'.';
        $alreadySeeded = Comment::where('commentable_id', $model->id)
            ->where('commentable_type', $type)
            ->where('user_id', $author->id)
            ->where('comment', $comment)
            ->exists();
        if (!$alreadySeeded) {
            Comment::create([
                'commentable_id' => $model->id,
                'commentable_type' => $type,
                'comment' => $comment,
                'user_id' => $author->id,
                'parent_id' => null,
            ]);
        }
    }
}
