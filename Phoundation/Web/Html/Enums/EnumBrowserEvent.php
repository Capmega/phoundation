<?php

/**
 * Enum BrowserEvents
 *
 * Browser events that can be used with Javascript
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

enum EnumBrowserEvent: string
{
    case abort                        = 'abort';
    case activate                     = 'activate';
    case addstream                    = 'addstream';
    case addtrack                     = 'addtrack';
    case afterprint                   = 'afterprint';
    case afterscriptexecute           = 'afterscriptexecute';
    case animationcancel              = 'animationcancel';
    case animationend                 = 'animationend';
    case animationiteration           = 'animationiteration';
    case animationstart               = 'animationstart';
    case appinstalled                 = 'appinstalled';
    case audioend                     = 'audioend';
    case audioprocess                 = 'audioprocess';
    case audiostart                   = 'audiostart';
    case auxclick                     = 'auxclick';
    case beforeinput                  = 'beforeinput';
    case beforeprint                  = 'beforeprint';
    case beforescriptexecute          = 'beforescriptexecute';
    case beforeunload                 = 'beforeunload';
    case beginEvent                   = 'beginEvent';
    case blocked                      = 'blocked';
    case blur                         = 'blur';
    case boundary                     = 'boundary';
    case bufferedamountlow            = 'bufferedamountlow';
    case cancel                       = 'cancel';
    case canplay                      = 'canplay';
    case canplaythrough               = 'canplaythrough';
    case change                       = 'change';
    case click                        = 'click';
    case close                        = 'close';
    case closing                      = 'closing';
    case complete                     = 'complete';
    case compositionend               = 'compositionend';
    case compositionstart             = 'compositionstart';
    case compositionupdate            = 'compositionupdate';
    case connect                      = 'connect';
    case connectionstatechange        = 'connectionstatechange';
    case contentdelete                = 'contentdelete';
    case contextmenu                  = 'contextmenu';
    case copy                         = 'copy';
    case cuechange                    = 'cuechange';
    case cut                          = 'cut';
    case datachannel                  = 'datachannel';
    case dblclick                     = 'dblclick';
    case devicechange                 = 'devicechange';
    case devicemotion                 = 'devicemotion';
    case deviceorientation            = 'deviceorientation';
    case DOMActivate                  = 'DOMActivate';
    case DOMContentLoaded             = 'DOMContentLoaded';
    case DOMMouseScroll               = 'DOMMouseScroll';
    case drag                         = 'drag';
    case dragend                      = 'dragend';
    case dragenter                    = 'dragenter';
    case dragleave                    = 'dragleave';
    case dragover                     = 'dragover';
    case dragstart                    = 'dragstart';
    case drop                         = 'drop';
    case durationchange               = 'durationchange';
    case emptied                      = 'emptied';
    case end                          = 'end';
    case ended                        = 'ended';
    case endEvent                     = 'endEvent';
    case enterpictureinpicture        = 'enterpictureinpicture';
    case error                        = 'error';
    case FileReader                   = 'FileReader';
    case focus                        = 'focus';
    case focusin                      = 'focusin';
    case focusout                     = 'focusout';
    case formdata                     = 'formdata';
    case fullscreenchange             = 'fullscreenchange';
    case fullscreenerror              = 'fullscreenerror';
    case gamepadconnected             = 'gamepadconnected';
    case gamepaddisconnected          = 'gamepaddisconnected';
    case gatheringstatechange         = 'gatheringstatechange';
    case gesturechange                = 'gesturechange';
    case gestureend                   = 'gestureend';
    case gesturestart                 = 'gesturestart';
    case gotpointercapture            = 'gotpointercapture';
    case hashchange                   = 'hashchange';
    case icecandidate                 = 'icecandidate';
    case icecandidateerror            = 'icecandidateerror';
    case iceconnectionstatechange     = 'iceconnectionstatechange';
    case icegatheringstatechange      = 'icegatheringstatechange';
    case input                        = 'input';
    case inputsourceschange           = 'inputsourceschange';
    case install                      = 'install';
    case invalid                      = 'invalid';
    case keydown                      = 'keydown';
    case keypress                     = 'keypress';
    case keyup                        = 'keyup';
    case languagechange               = 'languagechange';
    case leavepictureinpicture        = 'leavepictureinpicture';
    case load                         = 'load';
    case loadeddata                   = 'loadeddata';
    case loadedmetadata               = 'loadedmetadata';
    case loadend                      = 'loadend';
    case loadstart                    = 'loadstart';
    case lostpointercapture           = 'lostpointercapture';
    case mark                         = 'mark';
    case merchantvalidation           = 'merchantvalidation';
    case message                      = 'message';
    case messageerror                 = 'messageerror';
    case mousedown                    = 'mousedown';
    case mouseenter                   = 'mouseenter';
    case mouseleave                   = 'mouseleave';
    case mousemove                    = 'mousemove';
    case mouseout                     = 'mouseout';
    case mouseover                    = 'mouseover';
    case mouseup                      = 'mouseup';
    case mousewheel                   = 'mousewheel';
    case mute                         = 'mute';
    case negotiationneeded            = 'negotiationneeded';
    case nomatch                      = 'nomatch';
    case notificationclick            = 'notificationclick';
    case offline                      = 'offline';
    case online                       = 'online';
    case open                         = 'open';
    case orientationchange            = 'orientationchange';
    case pagehide                     = 'pagehide';
    case pageshow                     = 'pageshow';
    case paste                        = 'paste';
    case pause                        = 'pause';
    case payerdetailchange            = 'payerdetailchange';
    case paymentmethodchange          = 'paymentmethodchange';
    case PictureInPictureWindow       = 'PictureInPictureWindow';
    case play                         = 'play';
    case playing                      = 'playing';
    case pointercancel                = 'pointercancel';
    case pointerdown                  = 'pointerdown';
    case pointerenter                 = 'pointerenter';
    case pointerleave                 = 'pointerleave';
    case pointerlockchange            = 'pointerlockchange';
    case pointerlockerror             = 'pointerlockerror';
    case pointermove                  = 'pointermove';
    case pointerout                   = 'pointerout';
    case pointerover                  = 'pointerover';
    case pointerup                    = 'pointerup';
    case popstate                     = 'popstate';
    case progress                     = 'progress';
    case push                         = 'push';
    case pushsubscriptionchange       = 'pushsubscriptionchange';
    case ratechange                   = 'ratechange';
    case readystatechange             = 'readystatechange';
    case rejectionhandled             = 'rejectionhandled';
    case removestream                 = 'removestream';
    case removetrack                  = 'removetrack';
    case removeTrack                  = 'removeTrack';
    case repeatEvent                  = 'repeatEvent';
    case reset                        = 'reset';
    case resize                       = 'resize';
    case resourcetimingbufferfull     = 'resourcetimingbufferfull';
    case result                       = 'result';
    case resume                       = 'resume';
    case RTCPeerConnection            = 'RTCPeerConnection';
    case scroll                       = 'scroll';
    case search                       = 'search';
    case seeked                       = 'seeked';
    case seeking                      = 'seeking';
    case select                       = 'select';
    case selectedcandidatepairchange  = 'selectedcandidatepairchange';
    case selectend                    = 'selectend';
    case selectionchange              = 'selectionchange';
    case selectstart                  = 'selectstart';
    case shippingaddresschange        = 'shippingaddresschange';
    case shippingoptionchange         = 'shippingoptionchange';
    case signalingstatechange         = 'signalingstatechange';
    case slotchange                   = 'slotchange';
    case soundend                     = 'soundend';
    case soundstart                   = 'soundstart';
    case speechend                    = 'speechend';
    case speechstart                  = 'speechstart';
    case squeeze                      = 'squeeze';
    case squeezeend                   = 'squeezeend';
    case squeezestart                 = 'squeezestart';
    case stalled                      = 'stalled';
    case start                        = 'start';
    case statechange                  = 'statechange';
    case storage                      = 'storage';
    case submit                       = 'submit';
    case success                      = 'success';
    case suspend                      = 'suspend';
    case TextTrackList                = 'TextTrackList';
    case timeout                      = 'timeout';
    case timeupdate                   = 'timeupdate';
    case toggle                       = 'toggle';
    case tonechange                   = 'tonechange';
    case touchcancel                  = 'touchcancel';
    case touchend                     = 'touchend';
    case touchmove                    = 'touchmove';
    case touchstart                   = 'touchstart';
    case track                        = 'track';
    case transitioncancel             = 'transitioncancel';
    case transitionend                = 'transitionend';
    case transitionrun                = 'transitionrun';
    case transitionstart              = 'transitionstart';
    case unhandledrejection           = 'unhandledrejection';
    case unload                       = 'unload';
    case unmute                       = 'unmute';
    case upgradeneeded                = 'upgradeneeded';
    case versionchange                = 'versionchange';
    case visibilitychange             = 'visibilitychange';
    case voiceschanged                = 'voiceschanged';
    case volumechange                 = 'volumechange';
    case vrdisplayactivate            = 'vrdisplayactivate';
    case vrdisplayblur                = 'vrdisplayblur';
    case vrdisplayconnect             = 'vrdisplayconnect';
    case vrdisplaydeactivate          = 'vrdisplaydeactivate';
    case vrdisplaydisconnect          = 'vrdisplaydisconnect';
    case vrdisplayfocus               = 'vrdisplayfocus';
    case vrdisplaypointerrestricted   = 'vrdisplaypointerrestricted';
    case vrdisplaypointerunrestricted = 'vrdisplaypointerunrestricted';
    case vrdisplaypresentchange       = 'vrdisplaypresentchange';
    case waiting                      = 'waiting';
    case webglcontextcreationerror    = 'webglcontextcreationerror';
    case webglcontextlost             = 'webglcontextlost';
    case webglcontextrestored         = 'webglcontextrestored';
    case webkitmouseforcechanged      = 'webkitmouseforcechanged';
    case webkitmouseforcedown         = 'webkitmouseforcedown';
    case webkitmouseforceup           = 'webkitmouseforceup';
    case webkitmouseforcewillbegin    = 'webkitmouseforcewillbegin';
    case WebSocket                    = 'WebSocket';
    case wheel                        = 'wheel';
    case XMLHttpRequest               = 'XMLHttpRequest';
}
