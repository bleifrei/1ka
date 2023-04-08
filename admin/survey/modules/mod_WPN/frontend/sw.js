/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var cacheName = 'CSv4';

var cachedFiles = [
    /*'/frontend/',
    '/index.html',
    '/manifest.json',
    '/js/main.js',
    '/js/production.css',
    '/css/main.css'
    '/frontend/css/normalize.min.css',
    '/frontend/img/cassidy.jpg',
    '/frontend/img/cramer.jpg',
    '/frontend/img/duffy.jpg',
    '/frontend/img/gabor.jpg',
    '/frontend/img/aths.png',
    '/frontend/img/share.png',
    '/frontend/apple-touch-icon.png',
    '/frontend/android-chrome-192x192.png'*/
];

self.addEventListener('install', function(evt){
    console.log('Service Worker Install Event');
    //Add the file to the cache
    evt.waitUntil(
        caches.open(cacheName).then(function(cache){
            console.log('Caching Files');
            return cache.addAll(cachedFiles);
        }).then(function(){
            return self.skipWaiting();
        }).catch(function(err){
            console.log('Cache Failed', err);
        })    
    );
});

self.addEventListener('activate', function(evt){
    console.log('Service Worker Activated');
    evt.waitUntil(
       caches.keys().then(function(keyList){
           return Promise.all(keyList.map(function(key){
               if(key !== cacheName){
                   console.log('Removing Old Cache', key);
                   return caches.delete(key)
               }
           }));
       })
    );
    return self.clients.claim();
});

/*self.addEventListener('fetch', function(evt){
    console.log('Fetch Event' + evt.request.url);
    evt.respondWith(
       caches.match(evt.request).then(function(response){
           return response || fetch(evt.request);
       })
    );
});*/

function closeNotification(msg, evt){
    console.log(msg, evt.notification.data);
    evt.notification.close();
}

self.addEventListener('notificationclose', function(evt){
    closeNotification('Notification Closed', evt);
});

self.addEventListener('notificationclick', function(evt){
    if(evt.action !== 'close'){
        evt.waitUntil(
            self.clients.matchAll({type: 'window', includeUncontrolled: 'true'}).then(function(allClients){
                console.log(allClients);
                var matchingClient = null;
                for(var i = 0; i<allClients.length; i++){
                    if(allClients[i].visibilityState === 'visible'){
                        matchingClient = allClients[i];
                        console.log('Navigating');
                        matchingClient.navigate(evt.notification.data.loc);
                        break;
                    }
                }
                if(matchingClient === null){
                    console.log('Opening');
                    self.clients.openWindow(evt.notification.data.loc);
                }
            })
        );
    }
    closeNotification('Notification Clicked', evt);
});

self.addEventListener('pushsubscriptionchange', function(e) {
  // remove the entry from DB
  console.log(e);
});

self.addEventListener('push', function(evt){
    console.log('Push Message Received');
    if(evt.data){
        console.log('Data Received');
        var payload = JSON.parse(evt.data.text());
        console.log(evt.data.text());
        var options = {
                body: payload.message,
                icon: 'admin/survey/modules/mod_WPN/frontend/android-chrome-192x192.png',
            data: {
                timestamp: Date.now(),
                loc: payload.link
            },
            actions: [
                {action: 'go', title: 'Fill out'}
            ]
        };
        evt.waitUntil(
            self.registration.showNotification(payload.title, options)  
        );
    }
});