window.onhashchange = function(){
    //Header is fixed, need to slide down some to see sectionHead
    setTimeout('scrollBy(0,-110)',10);
};
var hidden = true;
function toggleNav(){
    if(hidden){
        document.getElementsByTagName('nav')[0].style.display = 'block';
    }else{
        document.getElementsByTagName('nav')[0].style.display = 'none';
    }
    hidden = !hidden;
}
var pwaSupport = false;   

if('serviceWorker' in navigator){
    pwaSupport = true;
    //register the service worker
    navigator.serviceWorker.register('sw.js').then(function(result){
        console.log('Service Worker Registered');
        console.log('Scope: ' + result.scope);
        console.log('calling');
        subscribeDivControl();
        
        /*
        if('Notification' in window){
            console.log('Notifications Supported');
            Notification.requestPermission(function(status){
                console.log('Notification Status: ', status);
            });
            var options = {
                body: 'See What\'s New',
                icon: 'android-chrome-192x192.png',
                data: {
                    timestamp: Date.now(),
                    loc: 'index.html#info'
                },
                actions: [
                    {action: 'go', title: 'Go Now'}
                ]
            };
            notify('NCC Computer Science', options);
        }
        */
    }, function(error){
        console.log('Service Worker Regiatration Failed: '+ error);
    });
}else{
    document.getElementById('notif_not_supported_div').style.display='block';
    console.log('Service Workers Not Supported');
}

function notify(title, options){
    if(Notification.permission === 'granted'){
        navigator.serviceWorker.ready.then(function(reg){
            reg.showNotification(title, options);
        });
    }
}

var installEvt;
window.addEventListener('beforeinstallprompt', function(evt){
    console.log('Before Install Prompt');
    installEvt = evt;
    evt.preventDefault();
    //document.getElementById('addToHomeScreen').style.display = 'block';
});

function hidePrompt(){
    //document.getElementById('addToHomeScreen').style.display = 'none';
}

function installApp(){
    hidePrompt();
    installEvt.prompt();
    installEvt.userChoice.then(function(result){
        if(result.outcome === 'accepted')
            console.log('App Installed');
        else
            console.log('App Not Installed');
    });
}

window.addEventListener('appinstalled', function(evt){
    console.log('App Installed Event');
});

window.onload = function(){
    if(pwaSupport){
        var p = navigator.platform;
        if(p === 'iPhone' || p === 'iPad' || p === 'iPod'){
            if(!navigator.standalone){
                var lastShown = parseInt(localStorage.getItem('lastShown'));
                var now = new Date().getTime();
                if(isNaN(lastShown) || (lastShown + 1000*60*60*24*7) <= now){
                    document.getElementById('instructions').style.display = 'block';
                    localStorage.setItem('lastShown', now);
                }
            }
        }
    }
};

function hideInstructions(){
    document.getElementById('instructions').style.display = 'none';
}

function clickButtonSubscribe(){
    subscribeToPush();
    subscribeDivControl();
}

function subscribeDivControl(){
    if(Notification.permission != 'granted'){
        document.getElementById('notif_join_div').style.display='block';
        document.getElementById('notif_joined_div').style.display='none';
        document.getElementById('notification_permission_warning').style.display='none';
    }
    else {
        document.getElementById('notif_joined_div').style.display='block';
        document.getElementById('notif_join_div').style.display='none';
    }
}

function subscribeToPush(){
    console.log('subscribeToPush');
    navigator.serviceWorker.ready.then(function(reg){
        console.log(reg);
        reg.pushManager.subscribe({
            userVisibleOnly:true,
            applicationServerKey: urlBase64ToUint8Array('BNVIBdCsC6vkmByQJ861pusHN1mV76X3mvAa1u4PxmleTv2m2whcEu9Elhh8Qz3XnqV6k58YCSVqaafl3bhPKLU')
        }).then(function(sub){
            subscribeDivControl();
            var json = JSON.parse(JSON.stringify(sub));
            console.log(json);
            /*console.log(JSON.stringify(sub));
            console.log(sub.json());*/
            console.log('User Subscribed');
            //var json = {endpoint:"https://fcm.googleapis.com/fcm/send/deaedc3PCAg:APA91bGy7QpBtbuokjOQv0Y_BcSOujpabeRY6PG5MUbcsOpf7kZaKTmJMb1jYmW03rPRSIY1shFlzh3UOI4hItQoHlzp6yNuPamxOwgbIbK1tG7oiRaUplQBNC8dN3qwm52bEOPgbqBX",expirationTime:null,keys:{p256dh:"BIIbHDXNbOGKG-gYec7a8DMpqst2Uxavo_p1MS695lvPJ1ZHO0audpMPRSWwae5BmaHCN6MYC2rThAsGlamS3sw",auth:"7P5IQKoInqQnTOBG1ZzNgw"}};

            $.post('../../../api/api.php?action=wpnAddSubscription&identifier=wpn', json, function(data){console.log(data);});
        
        }).catch(function (err){
            //console.log(err);
            document.getElementById('notification_permission_warning').style.display='block';
        });
    });
}

/**
 * urlBase64ToUint8Array
 * 
 * @param {string} base64String a public vavid key
 */
function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - base64String.length % 4) % 4);
    var base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    var rawData = window.atob(base64);
    var outputArray = new Uint8Array(rawData.length);

    for (var i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

