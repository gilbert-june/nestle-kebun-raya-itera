import { Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard';
import { LandingComponent } from './landing/landing';
import { ManageAccountComponent } from './manage-account/manage-account';
import { OAuthCallbackComponent } from './oauth-callback/oauth-callback';
import { ExportComponent } from './export/export';
import { ContactComponent } from './contact/contact';
import { AuthGuard } from './auth.guard';

export const routes: Routes = [
  { path: '', component: LandingComponent },
  { path: 'oauth-callback', component: OAuthCallbackComponent }, // Special route for OAuth callback
  { path: 'dashboard', component: DashboardComponent, canActivate: [AuthGuard] },
  { path: 'manage-account', component: ManageAccountComponent, canActivate: [AuthGuard] },
  { path: 'export', component: ExportComponent, canActivate: [AuthGuard] },
  { path: 'contact', component: ContactComponent },
  { path: '**', redirectTo: '' }
];
