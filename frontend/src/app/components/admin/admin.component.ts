import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterOutlet } from '@angular/router';
import { MatSidenavModule } from '@angular/material/sidenav';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatListModule } from '@angular/material/list';
import { MatMenuModule } from '@angular/material/menu';
import { MatCardModule } from '@angular/material/card';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { AuthService, User } from '../../services/auth.service';

@Component({
  selector: 'app-admin',
  standalone: true,
  imports: [
    CommonModule,
    RouterOutlet,
    MatSidenavModule,
    MatToolbarModule,
    MatButtonModule,
    MatIconModule,
    MatListModule,
    MatMenuModule,
    MatCardModule,
    MatSnackBarModule
  ],
  template: `
    <mat-sidenav-container class="admin-container">
      <mat-sidenav #sidenav mode="side" opened class="admin-sidenav">
        <div class="sidenav-header">
          <h2>Admin Panel</h2>
        </div>
        
        <mat-nav-list>
          <a mat-list-item routerLink="/admin/dashboard" routerLinkActive="active">
            <mat-icon>dashboard</mat-icon>
            <span>Dashboard</span>
          </a>
          
          <a mat-list-item routerLink="/admin/companies" routerLinkActive="active">
            <mat-icon>business</mat-icon>
            <span>Bedrijven</span>
          </a>
          
          <a mat-list-item routerLink="/admin/users" routerLinkActive="active">
            <mat-icon>people</mat-icon>
            <span>Gebruikers</span>
          </a>
          
          <a mat-list-item routerLink="/admin/vacancies" routerLinkActive="active">
            <mat-icon>work</mat-icon>
            <span>Vacatures</span>
          </a>
          
          <a mat-list-item routerLink="/admin/categories" routerLinkActive="active">
            <mat-icon>category</mat-icon>
            <span>Categorieën</span>
          </a>
          
          <a mat-list-item routerLink="/admin/matches" routerLinkActive="active">
            <mat-icon>compare_arrows</mat-icon>
            <span>Matches</span>
          </a>
          
          <a mat-list-item routerLink="/admin/interviews" routerLinkActive="active">
            <mat-icon>event</mat-icon>
            <span>Interviews</span>
          </a>
          
          <a mat-list-item routerLink="/admin/notifications" routerLinkActive="active">
            <mat-icon>notifications</mat-icon>
            <span>Notificaties</span>
          </a>
          
          <a mat-list-item routerLink="/admin/email-templates" routerLinkActive="active">
            <mat-icon>email</mat-icon>
            <span>E-mail Templates</span>
          </a>
        </mat-nav-list>
      </mat-sidenav>
      
      <mat-sidenav-content class="admin-content">
        <mat-toolbar color="primary">
          <button mat-icon-button (click)="sidenav.toggle()">
            <mat-icon>menu</mat-icon>
          </button>
          
          <span class="toolbar-spacer"></span>
          
          <button mat-icon-button [matMenuTriggerFor]="userMenu">
            <mat-icon>account_circle</mat-icon>
          </button>
          
          <mat-menu #userMenu="matMenu">
            <button mat-menu-item (click)="logout()">
              <mat-icon>logout</mat-icon>
              <span>Uitloggen</span>
            </button>
          </mat-menu>
        </mat-toolbar>
        
        <div class="content-area">
          <router-outlet></router-outlet>
        </div>
      </mat-sidenav-content>
    </mat-sidenav-container>
  `,
  styles: [`
    .admin-container {
      height: 100vh;
    }
    
    .admin-sidenav {
      width: 250px;
      background-color: #f5f5f5;
    }
    
    .sidenav-header {
      padding: 16px;
      border-bottom: 1px solid #ddd;
    }
    
    .sidenav-header h2 {
      margin: 0;
      color: #333;
    }
    
    .admin-content {
      display: flex;
      flex-direction: column;
    }
    
    .toolbar-spacer {
      flex: 1 1 auto;
    }
    
    .content-area {
      padding: 20px;
      flex: 1;
      overflow-y: auto;
    }
    
    .mat-nav-list a.active {
      background-color: #e3f2fd;
      color: #1976d2;
    }
    
    .mat-nav-list a {
      display: flex;
      align-items: center;
      gap: 12px;
    }
  `]
})
export class AdminComponent implements OnInit {
  currentUser: User | null = null;

  constructor(
    private authService: AuthService,
    private router: Router,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit() {
    this.currentUser = this.authService.getCurrentUser();
    
    if (!this.currentUser) {
      this.router.navigate(['/login']);
      return;
    }
  }

  logout() {
    this.authService.logout().subscribe({
      next: () => {
        this.router.navigate(['/login']);
        this.snackBar.open('Uitgelogd', 'Sluiten', { duration: 3000 });
      },
      error: (error) => {
        console.error('Logout error:', error);
        this.snackBar.open('Fout bij uitloggen', 'Sluiten', { duration: 3000 });
      }
    });
  }
}
