<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AudioPost;

class AudioPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $appURL = env('APP_URL');
        AudioPost::truncate();
        $datas = [
            [
                'name' => 'Takeover',
                'src_url' => "$appURL/storage/audio/legacy/takeover.mp3",
                'full_text' => <<<EOT
                [ti:Takeover]
                [ar:1Spirit and Theophius Sunday]
                [length:13:55.10]
                
                [00:10.49]Pre-Verse:
                [00:35.73]I cannot know you by myself,
                [00:40.26]Holy Spirit take over
                [00:44.62]I cannot go deeper by myself,
                [00:48.05]Except you help me go deeper.
                [00:51.85][Tongues]
                [01:05.61]Verse 1:
                [01:21.86]Take over! Take over!
                [01:29.46]Take over! Jesus take over!
                [01:35.78]I cannot know you by myself,
                [01:40.17]Holy Spirit take over
                [01:44.39]I cannot see you by myself,
                [01:48.39]Unless you take over
                [01:52.73]Take over! Jesus take over!
                [02:01.46]Takeover Jesus take over 
                [02:07.81]I cannot see you by myself,
                [02:12.31]Unless you take over
                [02:16.15]I cannot see you as you are,
                [02:19.32]Unless you take over
                [02:24.42]Take Over ehh!
                [02:31.94][Tongues]
                [02:45.26]Verse 2:
                [02:52.48]Take over! Jesus take over!
                [02:58.83]Take over! Lord take over!
                [03:06.46]I cannot see you as you are,
                [03:10.16]Unless you help me to see
                [03:14.74]I cannot know you as you are,
                [03:18.32]Unless you help me to know you
                [03:22.42]I cannot be who you are,
                [03:26.25]Unless you make me as you are
                [03:30.39]I cannot be as you are,
                [03:33.40]Unless you help me to be
                [03:37.61]Take Over! Take Over!
                [03:46.61][Tongues]
                [04:12.41]Verse 3:
                [04:11.97]Take over! Jesus take over!
                [04:23.74]I cannot know you by myself
                [04:27.80]Jesus take over
                [04:34.47]I cannot see you as you are,
                [04:37.83]Unless you take over
                [04:42.12]Take over! Jesus take over!
                [04:49.40]Take Over eh! Take Over!
                [04:57.19]I cannot know you as you are,
                [05:01.27]Unless you help me to know you
                [05:04.55]I cannot see you as you are.
                [05:10.08][Tongues]
                [05:34.58]I’m tired of myself, so Jesus take over
                [05:44.49]I’m tired of myself, (tongue) take over (5x)
                [06:30.93]Take Over! Take Over!
                [06:46.03]I’m tired of myself, Jesus take over.
                [07:00.98][Tongues]
                [07:02.78]Bridge:
                [07:04.23]Take all of my song, take all of my word
                [07:11.97]Take all of my voice, take all of my skills
                [07:21.70]Take all of my writings, take all of my
                [07:27.99]Make me more, just as you.
                [07:34.93]Cause my worship, is you,
                [07:39.49]And your worship I know, Holy Ghost.
                [07:53.80]Bridge 2:
                [07:53.65]Take all of my song, take all of my word
                [08:01.79]Take all of my voice, take all of my skills
                [08:09.28]Take all of my writings, take all of my passion
                [08:17.28]Make me more, just as you.
                [08:24.18]Cause my worship, is you,
                [08:31.48](Interlude)
                [08:39.54]Say my worship, is you,
                [08:44.19]And your worship I know, Holy Ghost.
                [08:54.81]Verse 4:
                [08:56.34]Take Over! Take Over!
                [09:01.15]Take over! Jesus take over!
                [09:06.38]I’m tired of myself, take over,
                [09:16.67]I cannot do it by myself, take over.
                [09:23.89]Take Over! Take Over!
                [09:33.48]Take Over! Take Over!
                [09:39.99](That's sin that you're struggling with ask him to takeover)
                [09:46.16](you've tried all the measures that you can, ask him to takeover)
                [09:52.96][Tongues]
                [09:55.62]Take over! Jesus take over!
                [10:10.90]I’m tired of myself, Baba take over,
                [10:17.46]I cannot do it by myself, Jesus take over
                [10:27.50]I cannot know you by myself, unless you take over,
                [10:34.42]I cannot run this race by myself, unless you take over
                [10:44.55]I cannot sing you by myself, unless you take over

                EOT,
                'description' => 'A powerful worship ministration by Min Theophilus Sunday asking the Holy Spirit to takeover',
                'user_id' => 1,
                'poster_id' => 1,
                'poster_type' => 'user',
                'size' => 200000000,
                'length' => 835,
                'language' => 'en',
            ],

            [
                'name' => 'Take It Away',
                'src_url' => "$appURL/storage/audio/legacy/take_it_away.mp3",
                'full_text' => <<<EOT
                [ti:Take It Away (II)]
                [ar:Theophilus Sunday]
                [al:Chantss]
                [length:02:11.69]
                [00:00.99]Say Whatever doesn’t look like You
                [00:03.57]That is still reflecting in me
                [00:07.94]Whatever doesn’t show Your grace
                [00:12.55]That is magnifying self in me
                [00:17.29]Whatever doesn’t look like You
                [00:21.52]That is still reflecting in me
                [00:26.21]Whatever doesn’t show Your grace
                [00:30.29]That is magnifying self in me
                [00:35.15]Take it Away
                [00:55.07]Take it Away
                [01:04.18]Take it Away
                [01:14.30]Take it Away
                [01:23.70]Take it Away
                [01:33.25]Take it Away
                [01:38.67](Tongues)
                [01:52.70]Take it Away
                [02:02.03]Take it Away

                EOT,
                'description' => 'A powerful worship ministration by Min Theophilus Sunday asking for the purity of the Holy Sprit to reign in our lives. It is a prayer as well as a chant',
                'user_id' => 1,
                'poster_id' => 1,
                'poster_type' => 'user',
                'size' => 28000000,
                'length' => 131,
                'language' => 'en',
            ],

            [
                'name' => 'My Desire',
                'src_url' => "$appURL/storage/audio/legacy/my-desire.mp3",
                'full_text' => <<<EOT
                [ti:my_desire]
                [ar:Theophilus Sunday]
                [al:Spiritual Songs]
                [au:Ruach]
                [la:EN]
                [re:LRCgenerator.com]
                [ve:4.00]
                
                [00:16.70]My desire
                [00:22.19]my desire
                [00:24.57]my desire is to walk with you my saviour
                [00:29.24]on this holy journey until I am no more
                
                [00:33.89]My desire
                [00:37.11]my desire
                [00:40.77]my desire is to walk with you my saviour
                [00:45.96]on this holy journey until I am no more
                
                [00:51.38]Just to see your face and to know your ways
                [00:57.96]my desire is to walk with you my saviour
                [01:03.08]on this Holy journey until I am no more
                
                [01:07.94]Ne mi le
                [01:10.02]ne mi le
                
                [01:13.99]Ne me le ona okpa kpa we uju bi o mi
                
                [01:28.39]ku we kere ku we no nane
                
                [01:37.21]Ejona we o, Ejona we o
                
                [01:43.21]ku we kere ku we no nane
                
                [01:48.82]Ejona we o, Ejona we o
                
                
                [01:55.70]I choose you Jesus
                [02:01.67]I choose you
                [02:04.81]I choose you
                [02:08.31]I choose you over everything Jesus
                
                [02:19.08]I choose you Jesus
                [02:24.81]I choose you
                [02:28.47]I choose you
                [02:32.29]I choose you over everything Jesus
                
                [02:43.31]I choose you Jesus
                [02:52.64]I choose you
                [02:56.14]I choose you
                [02:59.49]I choose you over everything Jesus
                
                [03:08.14](I choose you lord, I choose you lord)
                
                [03:11.81]I choose you Jesus
                [03:18.22]I choose you
                [03:22.91]I choose you
                [03:25.46]I choose you over everything Jesus
                
                
                [03:37.50]My desire
                [03:43.51]My desire
                [03:46.99]my desire is to walk with you my saviour
                [03:54.28]on this Holy journey until I am no more
                
                [04:01.22]My desire
                [04:05.95]my desire
                [04:08.70]my desire is to walk with you my saviour
                [04:16.29]on this Holy journey until I am no more
                
                [04:23.09]My desire
                [04:26.88]my desire
                [04:30.76]my desire is to walk with you my saviour
                [04:37.55]on this Holy journey until I am no more
                
                [04:44.77]My desire
                [04:48.61]my desire
                [04:52.29]my desire is to walk with you my saviour
                [04:59.18]on this Holy journey until I am no more
                
                [05:06.69]My desire
                [05:10.07]my desire
                [05:13.51]my desire is to walk with you my saviour
                [05:20.60]on this Holy journey until I am no more
                
                [05:27.25]Just to see your face and to know your ways
                [05:34.69]My desire is to walk with you my saviour on this holy journey until I am no more
                
                [05:49.24]Just to see your face and to know your ways
                [05:55.23]My desire is to walk with you my saviour on this holy journey until I am no more
                
                [06:08.59](lift up your voice and declare )
                
                [06:08.93]my desire
                [06:12.52]my desire
                [06:15.61]my desire is to walk with you my saviour
                [06:22.40]on this Holy journey until I am no more
                
                [06:28.44]my desire
                [06:32.51]my desire
                [06:36.00]my desire is to walk with you my saviour
                [06:42.68]on this Holy journey until I am no more
                

                EOT,
                'description' => 'Deep Worship sound by Min Theophilus Sunday drawing us into intimacy as we proclaim the lord as our utmost desire Psalm 42:1',
                'user_id' => 1,
                'poster_id' => 1,
                'poster_type' => 'user',
                'size' => 6598875,
                'length' => 409,
                'language' => 'en',
            ],

            [
                'name' => 'Take Me Deeper',
                'src_url' => "$appURL/storage/audio/legacy/take-me-deeper.mp3",
                'full_text' => <<<EOT
                [ti:take_me_deeper]
                [ar:Theophilus Sunday]
                [al:Deep Chants]
                [au:Ruach]
                [la:EN]
                [re:LRCgenerator.com]
                [ve:4.00]
                
                
                [00:06.56]I wanna know your ways
                [00:11.65]I wanna walk in thy path
                [00:16.83]Help me to learn of you
                [00:21.20]Not of the works of thy hands
                
                [00:26.25]I wanna know your ways
                [00:30.39]I wanna walk in thy path
                [00:36.11]Help me to learn of you
                [00:40.76]Not of the works of thy hands
                
                [00:45.17]Take me deeper, deeper
                [00:51.69]Teach me the ways of the deep
                [00:54.63]Teach me the path of the ancients
                
                [00:59.86]Take me deeper
                [01:06.43]Teach me the ways of the deep
                [01:09.64]Teach me the path of the ancients
                
                [01:15.43]I wanna know your ways
                [01:21.94]I wanna walk in thy path
                [01:28.94]Help me to learn of you
                [01:35.64]Not of the works of thy hands
                
                [01:41.68]Take me deeper
                [01:47.75]Teach me the ways of the deep
                [01:51.15]Teach me the path of the ancients
                
                [01:55.88]Take me deeper
                [02:02.48]Teach me the ways of the deep
                [02:04.96]Teach me the path of the ancients
                
                
                [02:09.43]For the ways of the Lord, is the way (of wisdom)
                [02:25.16]For the ways of the Lord
                [02:42.35]For the ways of the Lord
                [03:02.37]See I choose the way (of the lord)
                
                [03:11.82](When we talk about the ways of the Lord, we don’t talk of a footpath)
                
                [03:35.78]For there is a place my heart yearns for lord
                [03:42.96]There is a place that am yearning for
                [03:51.25]It is a place, (where deep call to the deep)                    

                EOT,
                'description' => 'Deep Worship sound by Min Theophilus Sunday drawing us into intimacy as we ask God to teach us his ways Psalm 86:11',
                'user_id' => 1,
                'poster_id' => 1,
                'poster_type' => 'user',
                'size' => 8329093,
                'length' => 345,
                'language' => 'en',
            ],


            [
                'name' => 'One Sound',
                'src_url' => "$appURL/storage/audio/legacy/one-sound.mp3",
                'full_text' => <<<EOT
                [ti:one-sound]
                [ar:Theophilus Sunday]
                [al:Deep Chants]
                [au:Ruach]
                [la:EN]
                [re:LRCgenerator.com]
                [ve:4.00]
                
                
                [00:04.17](Speaking in tongues)
                
                [00:23.78]Aye, Aye,o, Aye, Aye o
                
                [00:50.11](Speaking in tongues)
                
                [01:12.15]I'm desperately searching,
                [01:15.24]earnestly longing,
                [01:18.32]For a place I cannot describe,
                
                [01:22.59]I'm desperately longing,
                [01:25.41]earnestly yearning,
                [01:28.45]For a place I cannot describe,
                
                [01:31.85]But when I find it I will know,
                
                [01:37.78]When I find it I will know
                
                [02:32.02](Speaking in tongues)
                
                [02:51.28]Aye, aye, o, Aye, aye, o
                
                [02:56.68](Tongues)
                
                [03:14.28](Lift up your vocie and pray, wherever you are)
                
                [03:38.03]When I find it I will know,
                
                [04:05.11]There's a depth I'm longing for
                [04:09.65]Oh this depth that I have found, keeps me longing for You
                [04:20.57]There's a place that I'm yearning for
                [04:26.02]Oh this place that I have found, keeps me longing for You
                
                [04:38.06]There's a place that I'm yearning for
                [04:42.77]Oh this place that I have found, keeps me longing for You
                
                
                [04:52.84]Oh Spirit, Spirit,
                [04:57.17]Oh Spirit, Spirit, Spirit, Spirit
                [05:01.29]Oh Spirit, Spirit,
                [05:04.10]Oh Spirit, Spirit, Spirit, Spirit
                [05:07.92]Oh Spirit, Spirit,
                [05:10.49]Oh Spirit, Spirit, Spirit, Spirit
                
                [05:14.62]My Lover, lover,
                [05:17.13]My lover, lover, lover, lover,
                
                [05:21.21]Oh Spirit, Spirit,
                [05:23.89]Oh Spirit, Spirit, Spirit, Spirit
                
                [05:27.89]Oh Spirit, Spirit,
                [05:30.47]Oh Spirit, Spirit, Spirit, Spirit
                
                [05:34.67]My Lover, lover,
                [05:37.29]My lover, lover, lover, lover,
                
                
                [05:47.24]My Jesus, Jesus,
                [05:50.72]My Jesus, Jesus, Jesus, Jesus,
                
                [05:54.81]My Jesus, Jesus,
                [05:57.64]My Jesus, Jesus, Jesus, Jesus,
                
                [06:01.73]My Jesus, Jesus,
                [06:04.22]My Jesus, Jesus, Jesus, Jesus,
                
                [06:08.30]My Jesus, Jesus,
                [06:11.48]My Jesus, Jesus, Jesus, Jesus,
                
                [06:14.28]
                [06:15.11]My Yahweh, Yahweh,
                [06:17.57]My Yahweh, Yahweh, Yahweh, Yahweh,
                
                [06:28.15]Oh this depth that I have found keeps me longing for you
                
                [07:05.20](Can we long for Jesus)
                [07:10.62](The more of him you see, the more of him you want to see)
                
                
                [07:37.08]Oh this place that I have found, keeps me longing for You
                
                [07:37.30]Hide me, hide me Your secret place,
                [07:37.51]Keep me in Your secret place.
                
                EOT,
                'description' => 'The draws us closer with a yearning for his kingdom, The kingdom of heaven which is the beautiful eternal life with him forever Psalm 91',
                'user_id' => 1,
                'poster_id' => 1,
                'poster_type' => 'user',
                'size' => 7486666,
                'length' => 466,
                'language' => 'en',
            ],

            [
                'name' => 'Come Oh Spirit',
                'src_url' => "$appURL/storage/audio/legacy/come-oh-spirit.mp3",
                'full_text' => <<<EOT
                [ti:come_oh_spirit]
                [ar:Theophilus Sunday]
                [al:Spiritual Songs]
                [au:Ruach]
                [la:EN]
                [re:LRCgenerator.com]
                [ve:4.00]
                
                [00:23.96]This is your temple
                [00:31.78]Come, have your way
                [00:39.31]We are your people
                [00:46.65]Come rule this place
                
                [00:54.14]This is your temple
                [01:01.36]Come have your way
                [01:08.15]We are your people
                [01:16.98]Come rule over us again
                
                [01:21.43]Come oh Spirit come
                [01:25.26]Come and have your way
                [01:28.98]Come oh Spirit come
                [01:32.65]Brood over us again
                [01:36.26]Come oh Spirit come
                [01:40.30]Make us sing mysteries
                [01:44.07]Come oh Spirit come
                [01:47.74]Rule over your church again
                
                [01:52.30]Oh Come
                
                [02:16.14](Tongues)
                
                [02:31.84](We need you in your church again)
                [02:34.90](We want to see your in our homes again)
                [02:38.97](We want to see you in our families again)
                [02:42.91](We want to see you in Nigeria again)
                [02:46.35](We want to see you in Africa again)
                [02:50.00](We want to see you in Asia again)
                [02:54.41](We want to see you oh Spirit)
                [02:58.23](Rule over the nations again)
                
                [03:02.18]Oh Come
                [03:09.03]Come oh Spirit come
                [03:12.65]Come and have your way
                
                [03:16.44]Come oh Spirit come
                [03:19.66]Help me seek your face
                
                [03:23.78]Come oh Spirit come
                [03:27.22]Let your nature be seen in your church
                
                [03:30.70]Come oh Spirit come
                [03:33.81]Let your glory be made real in your church again
                
                
                [03:37.79]Come oh Spirit come
                [03:41.84]Come and have your way
                
                [03:45.25]Come oh Spirit come oh
                [03:49.30]Come and seek your face
                
                [03:53.43]Brood over us again
                [03:56.54]Brood over the nations
                [04:00.07]Brood over South Africa
                [04:03.19]Brood over Asia again
                
                
                [04:07.59]Come oh Spirit come
                [04:13.09](Tongues)
                
                
                [04:15.77]Come oh Spirit come
                [04:21.80](Oh come Holy Spirit)
                [04:25.06]Oh Come , Oh Come
                
                [04:36.00](Everyone in this place can we cry oh come)
                [04:37.26]Oh Come, Oh Come
                [04:49.11](Over the nations we say come)
                
                [04:52.83](over your church we say come)
                
                [04:57.14](In Asia we say come)
                
                [05:01.24](In south America we say come)
                
                [05:10.18](In Africa we say come)
                
                [05:14.10](In Europe we say come)
                
                [05:18.40](In Antartica we say come)
                
                [05:22.04](In Russia we say come)
                
                [05:26.71]Oh, Come
                
                
                [05:57.26]Come oh Spirit come
                [06:00.86]Come and have your way
                
                [06:04.80]Come oh Spirit come
                [06:07.90]Interrupt my every plan
                
                [06:11.43]Come oh Spirit come
                [06:14.96]Break my zeal and give me your will
                
                [06:18.77]Come oh Spirit come
                [06:23.13]Take me through your path
                
                [06:26.65]Come oh Spirit come
                [06:29.97]Take my desire and give me yours
                
                [06:34.02](Even though my desire seems spiritual take it and give me yours)
                [06:41.59](Tongues)
                
                
                [07:02.63]Come oh Spirit come
                [07:06.84]Come and have your way oh
                [07:12.06]Oh, Oh,
                [07:19.07]Oh Come, Oh Come
                
                
                [07:27.52](Tongues)
                
                [07:34.25]Oh Come, Oh Come
                [07:52.68](Can you pray)
                [07:56.85](Tongues)
                
                [08:31.16]Come oh Spirit come
                [08:34.48]Come and have your way
                
                [08:37.98]Come oh Spirit come
                [08:48.26]Come and have your way
                
                
                [08:51.59]Come oh Spirit come
                [08:54.38]Fill your church again
                
                [08:57.01]Come oh Spirit come
                [09:01.25]Come and move in this place
                
                
                [09:04.91]Come oh Spirit come
                [09:07.65]Fill your church again
                
                [09:10.77]Come oh Spirit come
                [09:16.03]Come and have your way
                
                [09:27.40]Oh, Come
                
                
                [09:57.62]Come oh Spirit come
                [10:01.88]Come and have your way
                
                [10:04.62]Come oh Spirit come
                [10:08.30]We seek your face again
                
                [10:12.81]Oh, Come
                
                [10:29.39]Come, Oh Spirit Come
                
                [10:34.80]Ay ya ya ya ya
                [10:43.92]ya ya ya ya ya ya
                
                
                [11:01.19]Come oh Spirit come
                [11:04.77]Come and have your way
                                
                EOT,
                'description' => 'An invitation to the holy spirit, our King companion and promise, God with us',
                'user_id' => 1,
                'poster_id' => 1,
                'poster_type' => 'user',
                'size' => 10830096,
                'length' => 675,
                'language' => 'en',
            ],
        ];
        // \App\Models\AudioPost::factory(100)->create();
        // \App\Models\AudioPost::insert($datas);
        foreach ($datas as $audio ) {
            $audioPost = \App\Models\AudioPost::create($audio);
            $audioPost->hierarchies()->attach([1]);
        }
    }
}
